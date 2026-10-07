<?php

namespace App\Http\Controllers;

use App\Models\Senha;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class AtendimentoController extends Controller
{
    private function getParams()
    {
        $path = base_path('variante/params.json');
        if (!File::exists($path)) return ["PREFIXO" => "A", "RAZAO_PREFERENCIAL" => 1];
        return json_decode(File::get($path), true);
    }

    private function formatarSenha($senha)
    {
        $dados = [
            'codigo' => $senha->codigo,
            'tipo' => $senha->tipo,
            'emissao' => Carbon::parse($senha->emissao)->format('Y-m-d\TH:i:sP'),
            'status' => $senha->status
        ];
        if ($senha->chamada_em) {
            $dados['chamada_em'] = Carbon::parse($senha->chamada_em)->format('Y-m-d\TH:i:sP');
        }
        return $dados;
    }

    public function healthz()
    {
        return response()->json(["status" => "ok"], 200);
    }

    public function emitir(Request $request)
    {
        $validator = Validator::make($request->all(), ['tipo' => 'required|in:normal,preferencial']);
        if ($validator->fails()) return response()->json(["erro" => "tipo_invalido"], 422);

        $params = $this->getParams();
        $prefixo = $params['PREFIXO'];

        $senha = DB::transaction(function () use ($request, $prefixo) {
            $hoje = Carbon::today();
            $count = Senha::whereDate('emissao', $hoje)->count();
            $codigo = $prefixo . str_pad($count + 1, 3, '0', STR_PAD_LEFT);

            return Senha::create([
                'codigo' => $codigo,
                'tipo' => $request->tipo,
                'status' => 'aguardando',
                'emissao' => Carbon::now()
            ]);
        });

        return response()->json($this->formatarSenha($senha), 201);
    }

    public function proxima()
    {
        $params = $this->getParams();
        $razao = $params['RAZAO_PREFERENCIAL'];

        $resposta = DB::transaction(function () use ($razao) {
            $recentes = Senha::whereNotNull('chamada_em')->orderBy('chamada_em', 'desc')->limit($razao)->get();
            $streak = 0;
            foreach ($recentes as $r) {
                if ($r->tipo === 'preferencial') $streak++;
                else break;
            }

            $preferencial = Senha::where('status', 'aguardando')->where('tipo', 'preferencial')->orderBy('emissao', 'asc')->first();
            $normal = Senha::where('status', 'aguardando')->where('tipo', 'normal')->orderBy('emissao', 'asc')->first();

            if (!$preferencial && !$normal) return null;

            $proxima = ($streak < $razao) ? ($preferencial ?? $normal) : ($normal ?? $preferencial);
            
            $proxima->status = 'chamada';
            $proxima->chamada_em = Carbon::now();
            $proxima->save();

            return $proxima;
        });

        if (!$resposta) return response()->json(["erro" => "fila_vazia"], 404);
        return response()->json($this->formatarSenha($resposta), 200);
    }

    public function concluir($codigo)
    {
        $senha = Senha::where('codigo', $codigo)->first();
        if (!$senha) return response()->json(["erro" => "senha_nao_encontrada"], 404);
        if ($senha->status !== 'chamada') return response()->json(["erro" => "senha_nao_chamada"], 409);

        $senha->status = 'concluida';
        $senha->save();
        return response()->json($this->formatarSenha($senha), 200);
    }

    public function rechamar($codigo)
    {
        $senha = Senha::where('codigo', $codigo)->first();
        if (!$senha) return response()->json(["erro" => "senha_nao_encontrada"], 404);
        if ($senha->status !== 'chamada') return response()->json(["erro" => "senha_nao_chamada"], 409);

        $senha->chamada_em = Carbon::now();
        $senha->save();
        return response()->json($this->formatarSenha($senha), 200);
    }

    public function cancelar($codigo)
    {
        $senha = Senha::where('codigo', $codigo)->first();
        if (!$senha) return response()->json(["erro" => "senha_nao_encontrada"], 404);
        if ($senha->status !== 'aguardando') return response()->json(["erro" => "senha_nao_aguardando"], 409);

        $senha->status = 'cancelada';
        $senha->save();
        return response()->json($this->formatarSenha($senha), 200);
    }

    public function painel()
    {
        $senhas = Senha::whereNotNull('chamada_em')->orderBy('chamada_em', 'desc')->limit(5)->get();
        return response()->json(["chamadas" => $senhas->map(fn($s) => $this->formatarSenha($s))], 200);
    }
}
