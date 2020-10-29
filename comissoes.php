<?php

require_once("includes/config.php");
require_once('includes/functions.php');
require_once "agrupar_comissoes.php";
require_once "pagar_comissao.php";

$tblOperadoras = array();
$tblContas = array();
$tblFinalizado = array();
$tblComissoes = array();

# SELECIONAR AS COMISSOES QUE SERAO PROCESSADAS
$sql = "SELECT * FROM busca_comissoes where referencia like '%BRADESCO%' and data_inicial >= '2019-10-12' and data_final <= '2021-10-16'";
$select_comissoes = mysqli_query($conect, $sql);
while ($rs_comissoes = mysqli_fetch_array($select_comissoes)){
    array_push($tblComissoes, $rs_comissoes);
}
$comissoes = agruparComissoes($tblComissoes);

# OBTER ALGUNS DADOS DO BANCO PARA NAO FICAR PESQUISANDO TUDO NO BANCO
$sql = "SELECT * FROM tbl_operadora;";
$select_operadoras = mysqli_query($conect, $sql);
while ($rs_operadora = mysqli_fetch_array($select_operadoras)){
    array_push($tblOperadoras, $rs_operadora);
}

$sql = "SELECT * FROM tbl_contas;";
$select_contas = mysqli_query($conect, $sql);
while ($rs_conta = mysqli_fetch_array($select_contas)){
    array_push($tblContas, $rs_conta);
}

$sql = "SELECT * FROM tbl_finalizado;";
$select_finalizado = mysqli_query($conect, $sql);
while ($rs_finalizado = mysqli_fetch_array($select_finalizado)){
    array_push($tblFinalizado, $rs_finalizado);
}

# COLOCAR A PARTE AS COMISSOES QUE TEM INCLUSAO
$comissoesPagarAParte = array();
$comissaoNew = array();
foreach($comissoes as $comissao){
    if ($comissao['porcentagem'] < 50 && $comissao['parcela'] < 4){
        array_push($comissoesPagarAParte, $comissao);
    }else{
        array_push($comissaoNew, $comissao);
    }
}
$comissoes = $comissaoNew;

function processo($array){

    global $conect;
    global $tblOperadoras;
    global $tblContas;
    global $tblFinalizado;

    $comissoes = $array;

    $count = 0;
    $encontrados = 0;
    $comissoesEncontradas = array();
    $comissoesNaoEncontradas = array();

    for($i = 0 ; $i < sizeof($comissoes)-2; $i++){

        $comissaoRow = $comissoes[$i];
        

        $idOrigem = 0;
        $idDestino = 1;
        $idFinalizado = 0;

        $operadora = "";
        $portabilidade = "";
        $idOperadora = 0;
        
        $idTransacao = 1;
        $refDental = false;

        

        $idBuscaComissao = $comissaoRow['id'];
        $referencia = (string) strtoupper($comissaoRow['referencia']);
        $nomeContrato = $comissaoRow['nome_contrato'];
        $contrato = $comissaoRow['contrato_atual'];
        $proposta = $comissaoRow['proposta'];
        $data = $comissaoRow['data_pagamento'];
        $valor_calc = $comissaoRow['comissao'];
        $parcela = $comissaoRow['parcela'];
        $porcentagem = $comissaoRow['porcentagem'];

        if ($parcela > 3){
            $idTransacao = 7;
        }


        $ref_arr = explode(" ", $referencia);
        // echo "<p> => ".$referencia."</p>";
        switch(strtoupper($ref_arr[0])){
            case "AFFINITY":
                if (in_array("CNU", $ref_arr)){
                    $operadora = "AFFINITY UNIMED";
                }elseif(in_array("SOMPO", $ref_arr)){
                    $operadora = "AFFINITY SOMPO";
                }
                break;
            case "INTERMEDICA-PJ":
                $operadora = "NOTREDAME";
                break;
            case "INTERMEDICA-PME":
                $operadora = "NOTREDAME";
                $contrato = $proposta;
                break;
            case "AMIL":
                $operadora = "AMIL";
                $contrato = $proposta;
                break;
            case "NEXT":
                $operadora = "AMIL FÁCIL";
                $contrato = $proposta;
                break;
            case "BRADESCO-SAUDE":
                $operadora = "BRADESCO";
                break;
            case "BRADESCO-DENTAL":
                $operadora = "BRADESCO";
                $refDental = true;
                $contrato = $contrato - 1;
                break;
            default:
                $operadora = "";

        }
        // echo "<p> = ".$operadora."</p>";


        for($j = 0 ; $j < sizeof($tblOperadoras)-1; $j++){
            $operadoraRow = $tblOperadoras[$j];
            // echo "<p> ".$operadora."</p>";
            if( $operadora == $operadoraRow['titulo']){
                $idOperadora = $operadoraRow['id'];
                // echo "<p> ".$idOperadora."</p>";
                break;
            }
        }

        echo "<br><p style='color:blue;'>-> <strong>$operadora</strong></p>";
        echo "<p><strong>Busca: </strong>$idBuscaComissao (id_busca) - <strong>$nomeContrato</strong> (Nome Contrato) - $contrato (Numero da apolice)</p>";
        echo "<p><strong>Parcela: </strong>".$parcela."</p>";
        echo "<p><strong>Data Pagamento extraido: </strong>".$data."</p>";
        echo "<p><strong>Comissão: </strong>R$ ".$valor_calc."</p>";

        if ($idOperadora > 0){

            for($j = 0 ; $j <= sizeof($tblContas); $j++){
                $contasRow = $tblContas[$j];
                if ($idOperadora == 8){
                    $operadora = 'NEXT';
                }
                if( $operadora == $contasRow['titulo']){
                    $idOrigem = $contasRow['id'];
                    echo "<p> <strong>id_conta: </strong>".$idOrigem."</p>";
                    break;
                }
            }

            if($idOrigem > 0){
                $res = procuraPeloNumeroContratoAtual($contrato, $idOperadora);
                if($res){
                    $idFinalizado = $res['id'];
                    $portabilidade = $res['portabilidade'];
                    $contrato = $res['n_apolice'];
                    $operadoraFinalizado = $res['id_operadora'];
                    $nomeContrato = $res['razao_social'];

                    if ($operadoraFinalizado == 5){
                        $idOrigem = 22;
                    }
                    

                }else{
                    $res = procuraPelaRazaoSocial($nomeContrato, $idOperadora);
                    if($res){
                        $idFinalizado = $res['id'];
                        $portabilidade = $res['portabilidade'];
                        $nomeContrato = $res['razao_social'];
                    }else{
                        $buscaRazaoSocialArray = array();
                        if($idOperadora == 2){
                            // $sql = "select * from tbl_finalizado where razao_social like '%$nomeContrato%' and (id_operadora = $idOperadora or id_operadora = 5);";
                            $sql = "select id,id_operadora, id_tipo_plano, razao_social, n_apolice from tbl_finalizado where replace(replace(replace(replace(replace(razao_social, '-', ''), '.',''), ' ',''), '/',''), '&', '')  like '%".str_replace(' ', '', str_replace("&", "", $nomeContrato))."%' and (id_operadora = $idOperadora or id_operadora = 5);";
                        }else{
                            // $sql = "select * from tbl_finalizado where razao_social like '%$nomeContrato%' and where id_operadora = $idOperadora;";

                            $sql = "select id,id_operadora, id_tipo_plano, razao_social, n_apolice from tbl_finalizado where replace(replace(replace(replace(replace(razao_social, '-', ''), '.',''), ' ',''), '/',''), '&', '')  like '%".str_replace(' ', '', str_replace( "&", "", $nomeContrato))."%' and id_operadora = $idOperadora;";
                        }
                        // echo "<p>$sql</p>";
                        $buscaRazaoSocial = mysqli_query($conect, $sql);
                        while ($rs_busca = mysqli_fetch_array($buscaRazaoSocial)){
                            array_push($buscaRazaoSocialArray, $rs_busca);
                        }

                        if(sizeof($buscaRazaoSocialArray) == 1){
                            $row = $buscaRazaoSocialArray[0];
                            $idFinalizado = intval($row['id']);
                            $nomeContrato = $row['razao_social'];
                        }

                        
                    }
                }
            }else{
                echo "<p style='color:red;'>Não achou a conta de origem!</p>";
            }

            

            if ($idFinalizado > 0){
                echo "<h5> ID: <strong style='color:green;'>ID FINALIZADO: $idFinalizado </strong> encontrou</h5>";

                $listaParcelas = array();
                $listaParcelasBanco = array();

                for ($k = 1; $k <= $parcela; $k++){
                    array_push($listaParcelas, $k);
                }
                
                $trasacoes = array();
                $sql = "select * from tbl_transacoes where id_finalizado = $idFinalizado AND id_origem = $idOrigem;";
                
                // echo "<p>$sql</p>";
                $select_transacoes = mysqli_query($conect, $sql);
                while ($rs_trasacao = mysqli_fetch_array($select_transacoes)){
                    // echo "<h6> ID: <song style='color:orange;'>ID TRANSACOES: ".$rs_trasacao['id']." </strong> encontrou</h6>";

                    array_push($trasacoes, $rs_trasacao);
                    array_push($listaParcelasBanco, intval($rs_trasacao['parcela']));
                }
                
                # Procurar novamente mas com outro id_origem
                if (sizeof($trasacoes) == 0 && $idOrigem == 18 && $parcela > 1){
                    $idOrigem = 22;
                    $sql = "select * from tbl_transacoes where id_finalizado = $idFinalizado AND id_origem = $idOrigem;";
                
                    // echo "<p>$sql</p>";
                    $select_transacoes = mysqli_query($conect, $sql);
                    while ($rs_trasacao = mysqli_fetch_array($select_transacoes)){
                        // echo "<h6> ID: <song style='color:orange;'>ID TRANSACOES: ".$rs_trasacao['id']." </strong> encontrou</h6>";

                        array_push($trasacoes, $rs_trasacao);
                        array_push($listaParcelasBanco, intval($rs_trasacao['parcela']));
                    }
                }
                
                // echo "<p> </p>";
                // var_dump($listaParcelas);
                // echo "<p></p>";
                // var_dump($listaParcelasBanco);

                $listaParcelasNaoPagas = array_diff($listaParcelas, $listaParcelasBanco);
                // echo "<p></p>";
                // var_dump($listaParcelasNaoPagas);
                $refComissaoPendente = false;
                foreach($listaParcelasNaoPagas as $key){
                    if ($key == $parcela){
                        $refComissaoPendente = true;
                        echo "<h5><strong style='color:red;'>Falta pagar a ultima parcela lançada pela operadora: ".$key." </strong></h5>";
                    }else{
                        echo "<h5><strong style='color:red;'>Falta Pagar a parcela: ".$key." </strong>.</h5>";
                    }
                }
                


                $dadosComissao = [
                    'txt_id_finalizado' => $idFinalizado,
                    'valor_calc' => $valor_calc,
                    'id_origem' => $idOrigem,
                    'id_destino' => $idDestino,
                    'descricao' => $nomeContrato,
                    'data' => $data,
                    'txt_parcela' => $parcela,
                    'portabilidade' => $portabilidade,
                    'n_apolice' => $contrato,
                    'porcentagem' => $porcentagem,
                    'id_transacao' => $idTransacao,
                    'dental' => $refDental
                ];
                


                if($idFinalizado > 0 && $refComissaoPendente){
                    array_push($comissoesEncontradas, $dadosComissao);
                }else{
                    $sql = "select * from tbl_transacoes where id_finalizado = $idFinalizado and id_origem = $idOrigem and parcela = $parcela ;";
                    // echo $sql;
                    $select = mysqli_query($conect, $sql);
                    $ref = true;

                    $valor = (float) 0.0;
                    while ($rs = mysqli_fetch_array($select)){
                        $valor += floatval($rs['valor']);
                        // if (intval($rs['valor']) == intval($valor_calc)){
                        // echo strval($rs['valor']);
                        // echo " ";
                        // echo strval($valor_calc);
                        // echo " ";
                        // if ( strval($rs['valor']) == strval($valor_calc)){
                        //     $ref = false;

                        // }
                    }
                    echo "<p>Banco: R$ $valor Comissao: R$ $valor_calc</p>";
                    if(strval($valor) == strval($valor_calc)){
                        $ref = false;
                    }

                    if ($ref){
                        array_push($comissoesEncontradas, $dadosComissao);
                        echo "<h5><strong style='color:red;'>=> Falta pagar a ultima parcela lançada pela operadora: ".$parcela." </strong></h5>";
                    }else{
                        echo "<h5><strong style='color:#5da170;'>Já foi paga a parcela: ".$parcela." </strong></h5>";
                    }
                }

            }else{
                array_push($comissoesNaoEncontradas, $dadosComissao);
                echo "<p style='color:red;'>Não achou a o numero da apólice nem a razao social!</p>";
            }
        }else{
            echo "<p> Não encontrou a operadora!</p>";
        }

        echo "<p> <strong>Razao Social: </strong>".$nomeContrato."</p>";
        
    }

    $comissoesEncontradasJson = json_encode($comissoesEncontradas);
    $comissoesNaoEncontradasJson = json_encode($comissoesNaoEncontradas);
    $new = json_encode($comissoesPagarAParte);
    echo "
    <script> 
        let teste1 = $comissoesEncontradasJson ; console.log('Encontrados: ',teste1);
        let teste2 = $comissoesNaoEncontradasJson ; console.log('Nao Encontrados :', teste2);
        let teste3 = $new ; console.log('Parcelas que começaram a repetir :', teste3);
    </script>";

    // echo date("Y-m-d");
    foreach($comissoesEncontradas as $comissao){
        
        pagarComissoes($comissao);
    }

}
processo($comissoes);


function procuraPeloNumeroContratoAtual( $numeroContrato, $idOperadora){
    global $tblFinalizado;
    
    $numeroContratoSplit = explode("/", $numeroContrato);
    for($i = 0 ; $i <= sizeof($tblFinalizado); $i++){
        $finalizadoRow = $tblFinalizado[$i];
        // echo "<p>".$numeroContratoSplit[0]."</p>";
        // echo " ".$finalizadoRow['n_apolice'];
        if( $numeroContratoSplit[0] == $finalizadoRow['n_apolice'] || $numeroContrato == $finalizadoRow['n_apolice']){
            if ($idOperadora == 2){
                return $finalizadoRow;
            }
            if ($idOperadora == $finalizadoRow['id_operadora']){
                return $finalizadoRow;
            }
        }
    }

    return false;

}

function procuraPelaRazaoSocial( $nomeContrato, $idOperadora){
    global $tblFinalizado;
    
    for($i = 0 ; $i <= sizeof($tblFinalizado); $i++){
        $finalizadoRow = $tblFinalizado[$i];
        // echo "<p>".$nomeContrato."</p>";
        // echo " ".$finalizadoRow['razao_social'];
        if( strtoupper(trim($nomeContrato)) == strtoupper(trim($finalizadoRow['razao_social'])) ){
            if ($idOperadora == 2){
                return $finalizadoRow;
            }
            if ($idOperadora == $finalizadoRow['id_operadora']){
                return $finalizadoRow;
            }
        }
    }

    return false;
}

mysqli_close($conect);
?>