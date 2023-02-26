<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SimuladorController extends Controller
{
    private $dadosSimulador;
    private $simulacao = [];

    public function simular(Request $request)
    {
        $this->carregarArquivoDadosSimulador();
        $this->simularEmprestimo($request->valor_emprestimo);
        $this->filtrarInstituicao($request->instituicoes);
        $this->filtrarConvenio($request->convenios);
        $this->filtrarParcelas($request->parcela);
        return \response()->json($this->simulacao);
    }

    private function carregarArquivoDadosSimulador() : self
    {
        $this->dadosSimulador = json_decode(\File::get(storage_path("app/public/simulador/taxas_instituicoes.json")));
        return $this;
    }


    private function filtrarParcelas(int $parcelas){
        $aux = [];

        foreach ($this->simulacao as $chavePrimaria => $taxes) {
            foreach ($taxes as $data) {
                if($data['parcelas'] == $parcelas){
                    $aux[$chavePrimaria][] = $data;
                }
            }
        }
        $this->simulacao = $aux;

    }


    private function filtrarConvenio(array $convenios){

        $aux = [];
        foreach ($this->simulacao as $chavePrimaria => $taxes) {
            foreach ($taxes as $chaveSecundária => $data) {
                foreach ($convenios as $key => $convention) {
                    if($data['convenio'] == $convention){
                        $aux[$chavePrimaria][$chaveSecundária] = $data;
                    }
                }
            }
        }
        $this->simulacao = $aux;
    }

    private function simularEmprestimo(float $valorEmprestimo) : self
    {
        foreach ($this->dadosSimulador as $dados) {
            $this->simulacao[$dados->instituicao][] = [
                "taxa"            => $dados->taxaJuros,
                "parcelas"        => $dados->parcelas,
                "valor_parcela"    => $this->calcularValorDaParcela($valorEmprestimo, $dados->coeficiente),
                "convenio"        => $dados->convenio,
            ];
        }
        return $this;
    }

    private function calcularValorDaParcela(float $valorEmprestimo, float $coeficiente) : float
    {
        return round($valorEmprestimo * $coeficiente, 2);
    }

    private function filtrarInstituicao(array $instituicoes) : self
    {
        if (\count($instituicoes))
        {
            $arrayAux = [];
            foreach ($instituicoes AS $key => $instituicao)
            {
                if (\array_key_exists($instituicao, $this->simulacao))
                {
                     $arrayAux[$instituicao] = $this->simulacao[$instituicao];
                }
            }
            $this->simulacao = $arrayAux;
        }
        return $this;
    }
}
