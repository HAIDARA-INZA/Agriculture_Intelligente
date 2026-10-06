<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgroController extends Controller
{
    // Classes des modèles d'origine (cacao, mangue, tomate)
    private const LABELS = [
        'cacao' => ['BLACKPOD', 'FROSTY ROT', 'HEALTHY', 'MIRID'],
        'mangue' => ['Anthracnose', 'Bacterial Canker', 'Cutting Weevil', 'Die Back', 'Gall Midge', 'Healthy', 'Powdery Mildew', 'Sooty Mould'],
        'tomate' => ['Tomato___Bacterial_spot', 'Tomato___Early_blight', 'Tomato___Late_blight', 'Tomato___Leaf_Mold',
            'Tomato___Septoria_leaf_spot', 'Tomato___Spider_mites_Two-spotted_spider_mite', 'Tomato___Target_Spot',
            'Tomato___Tomato_Yellow_Leaf_Curl_Virus', 'Tomato___Tomato_mosaic_virus', 'Tomato___healthy'],
    ];

    private static function clean(string $l): string
    {
        return str_replace('_', ' ', str_replace('Tomato___', '', $l));
    }

    private function recommendation(string $label): string
    {
        $r = require app_path('Support/recos.php');
        $l = strtolower($label);
        foreach ($r['items'] as $it) {
            foreach ($it['keys'] as $k) {
                if (str_contains($l, $k)) {
                    return $it['text'];
                }
            }
        }
        if (str_contains($l, 'healthy') || str_contains($l, 'sain')) {
            return $r['healthy'];
        }
        return str_replace('{label}', self::clean($label), $r['default']);
    }

    public function cultures()
    {
        return response()->json(collect(self::LABELS)->map(fn ($l) => array_map(fn ($x) => ['raw' => $x, 'label' => self::clean($x)], $l)));
    }

    /* Le serveur valide, calcule le risque, associe la fiche de recommandation et enregistre. */
    public function diagnose(Request $request)
    {
        $d = $request->validate([
            'culture' => 'required|in:cacao,mangue,tomate',
            'label' => 'required|string|max:100',
            'confidence' => 'required|numeric|min:0|max:1',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
        ]);
        if (!in_array($d['label'], self::LABELS[$d['culture']], true)) {
            return response()->json(['message' => 'Maladie inconnue pour cette culture'], 422);
        }
        $u = $request->attributes->get('auth_user');
        $c = (float) $d['confidence'];
        $risk = $c > 0.75 ? 'Élevé' : ($c > 0.45 ? 'Moyen' : 'Faible');
        $healthy = str_contains(strtolower($d['label']), 'healthy');
        if ($healthy) {
            $risk = 'Faible';
        }

        $id = DB::table('diagnostics')->insertGetId([
            'user_id' => $u->id, 'culture' => $d['culture'], 'label' => $d['label'],
            'confidence' => round($c, 3), 'risk_level' => $risk,
            // Position facultative, arrondie à 3 décimales (règle de confidentialité du projet d'origine)
            'lat' => isset($d['lat']) ? round((float) $d['lat'], 3) : null,
            'lng' => isset($d['lng']) ? round((float) $d['lng'], 3) : null,
            'source' => 'demo', 'created_at' => now(),
        ]);

        return response()->json([
            'id' => $id, 'culture' => $d['culture'], 'label' => self::clean($d['label']), 'healthy' => $healthy,
            'confidence' => round($c, 3), 'risk_level' => $risk, 'source' => 'demo',
            'recommendation' => $this->recommendation($d['label']),
        ], 201);
    }

    private function cut()
    {
        return now()->subDays(30)->format('Y-m-d H:i:s'); // purge logique à 30 jours
    }

    public function mine(Request $request)
    {
        $u = $request->attributes->get('auth_user');
        return response()->json(
            DB::table('diagnostics')->where('user_id', $u->id)->where('created_at', '>=', $this->cut())
                ->orderByDesc('id')->limit(50)->get()
                ->map(function ($x) {
                    $x->label = self::clean($x->label);
                    return $x;
                })
        );
    }

    public function map(Request $request)
    {
        $u = $request->attributes->get('auth_user');
        $q = DB::table('diagnostics')->whereNotNull('lat')->where('created_at', '>=', $this->cut());
        if ($u->role !== 'admin') {
            $q->where('user_id', $u->id);
        }
        return response()->json($q->orderByDesc('id')->limit(300)->get(['id', 'culture', 'label', 'risk_level', 'lat', 'lng', 'created_at'])
            ->map(function ($x) {
                $x->label = self::clean($x->label);
                return $x;
            }));
    }

    public function offers()
    {
        return response()->json(DB::table('offers as o')->join('users as u', 'u.id', '=', 'o.user_id')
            ->orderBy('o.sold')->orderByDesc('o.id')->limit(60)
            ->get(['o.id', 'o.product', 'o.quantity', 'o.location', 'o.price', 'o.sold', 'u.name as seller']));
    }

    public function storeOffer(Request $request)
    {
        $d = $request->validate([
            'product' => 'required|string|max:100', 'quantity' => 'required|string|max:40',
            'location' => 'required|string|max:80', 'price' => 'required|integer|min:1|max:10000000',
        ]);
        $u = $request->attributes->get('auth_user');
        DB::table('offers')->insert($d + ['user_id' => $u->id, 'sold' => 0, 'created_at' => now()]);
        return response()->json(['message' => 'Offre publiée'], 201);
    }

    public function buy(Request $request, $id)
    {
        $u = $request->attributes->get('auth_user');
        $o = DB::table('offers')->where('id', $id)->first();
        if (!$o) {
            return response()->json(['message' => 'Offre introuvable'], 404);
        }
        if ($o->user_id === $u->id) {
            return response()->json(['message' => 'Vous ne pouvez pas acheter votre propre offre'], 422);
        }
        // Mise à jour conditionnelle : un seul acheteur possible
        $n = DB::table('offers')->where('id', $id)->where('sold', 0)->update(['sold' => 1, 'buyer_id' => $u->id]);
        if (!$n) {
            return response()->json(['message' => 'Offre déjà vendue'], 409);
        }
        return response()->json(['message' => 'Achat enregistré (simulation : aucun paiement réel)']);
    }

    public function zones()
    {
        return response()->json(DB::table('zones')->orderBy('id')->get());
    }

    public function harvest(Request $request, $id)
    {
        $d = $request->validate(['tonnes' => 'required|numeric|min:0.1|max:1000']);
        if (!DB::table('zones')->where('id', $id)->increment('volume_t', (float) $d['tonnes'])) {
            return response()->json(['message' => 'Zone introuvable'], 404);
        }
        return response()->json(['message' => $d['tonnes'] . ' T ajoutées au groupage']);
    }

    public function dashboard()
    {
        $cut = $this->cut();
        return response()->json([
            'producteurs' => DB::table('users')->where('role', 'producteur')->count(),
            'diagnostics' => DB::table('diagnostics')->where('created_at', '>=', $cut)->count(),
            'alertes' => DB::table('diagnostics')->where('created_at', '>=', $cut)->where('risk_level', 'Élevé')
                ->where('label', 'not like', '%healthy%')->count(),
            'volume_t' => round((float) DB::table('zones')->sum('volume_t'), 1),
            'offres' => DB::table('offers')->where('sold', 0)->count(),
            'par_culture' => DB::table('diagnostics')->where('created_at', '>=', $cut)->select('culture', DB::raw('COUNT(*) n'))->groupBy('culture')->get(),
            'top_maladies' => DB::table('diagnostics')->where('created_at', '>=', $cut)->where('label', 'not like', '%healthy%')
                ->select('label', DB::raw('COUNT(*) n'))->groupBy('label')->orderByDesc('n')->limit(5)->get()
                ->map(function ($x) {
                    $x->label = self::clean($x->label);
                    return $x;
                }),
            'par_jour' => DB::table('diagnostics')->where('created_at', '>=', now()->subDays(6)->startOfDay()->format('Y-m-d H:i:s'))
                ->select(DB::raw('DATE(created_at) d'), DB::raw('COUNT(*) n'))->groupBy('d')->orderBy('d')->get(),
        ]);
    }
}
