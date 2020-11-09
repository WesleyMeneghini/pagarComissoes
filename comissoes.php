<?php

require_once("includes/config.php");
require_once('includes/functions.php');
require_once "agrupar_comissoes.php";
require_once "pagar_comissao.php";

$salvar = false;
$log = true;

$tblOperadoras = array();
$tblContas = array();
$tblFinalizado = array();
$tblComissoes = array();

# SELECIONAR AS COMISSOES QUE SERAO PROCESSADAS
// $sql = "SELECT * FROM busca_comissoes where referencia like '%BRADESCO%' and data_inicial >= '2020-10-26' and data_final <= '2020-10-30'";
$sql = "select * FROM busca_comissoes where referencia like '%AFFINITY%';";
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

function processo($array){

    global $log;
    global $conect;
    global $tblOperadoras;
    global $tblContas;
    global $tblFinalizado;
    global $salvar;

    $comissoes = $array;

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
        echo json_encode($comissaoRow);
        $contrato = $comissaoRow['contrato_atual'];
        $proposta = $comissaoRow['proposta'];
        $data = $comissaoRow['data_pagamento'];
        $valor_calc = $comissaoRow['comissao'];
        $parcela = intval($comissaoRow['parcela']);
        $porcentagem = $comissaoRow['porcentagem'];
        
        echo "<h1>Parcela: $parcela</h1>";
        if ($parcela > 3){
            $idTransacao = 7;
        }
        echo "<h1>Transação: $idTransacao</h1>";
        
        $valorBrutoComissao = 0.0;
        $contaOrigemNome = "";

        $ref_arr = explode(" ", $referencia);
        switch(strtoupper($ref_arr[0])){
            case "AFFINITY":
                if (in_array("CNU", $ref_arr)){
                    $operadora = "UNIMED";
                    $contaOrigemNome = "AFFINITY UNIMED";
                    $idOperadora = 6;
                }elseif(in_array("SOMPO", $ref_arr)){
                    $operadora = "SOMPO";
                    $contaOrigemNome = "AFFINITY SOMPO";
                    $idOperadora = 12;
                }

                $contrato = $proposta;
                break;
            case "INTERMEDICA-PJ":
                $operadora = "NOTREDAME";
                break;
            case "INTERMEDICA-PME":
                $operadora = "NOTREDAME";
                if ($proposta > 0){
                    $contrato = $proposta;
                }
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

        for($j = 0 ; $j < sizeof($tblOperadoras)-1; $j++){
            $operadoraRow = $tblOperadoras[$j];
            // echo "<p> ".$operadora."</p>";
            if( $operadora == $operadoraRow['titulo']){
                $idOperadora = $operadoraRow['id'];
                // echo "<p> ".$idOperadora."</p>";
                break;
            }
        }

        if ($log){

            echo "<br><p style='color:blue;'>-> <strong>$operadora</strong></p>";
            // echo "<p><strong>Busca: </strong>$idBuscaComissao (id_busca) - <strong>$nomeContrato</strong> (Nome Contrato) - $contrato (Numero da apolice)</p>";
            echo "<p><strong>Parcela: </strong>".$parcela."</p>";
            echo "<p><strong>Data Pagamento extraido: </strong>".$data."</p>";
            echo "<p><strong>Comissão: </strong>R$ ".$valor_calc."</p>";
            echo "<p><strong>Porcentagem: </strong>".$porcentagem."%</p>";
        }


        if ($parcela >= 6){
            $parcelaPesquisa = 6;
        }else{
            $parcelaPesquisa = $parcela;
        }

        $sql = "SELECT * FROM tbl_porcentagem_comissoes WHERE id_operadora = $idOperadora AND parcela = $parcelaPesquisa;";
        // echo $sql;
        $select = mysqli_query($conect, $sql);
        $porcentagemComissoes = array();
        while ($rs_porcentagem = mysqli_fetch_array($select)){
            array_push($porcentagemComissoes, $rs_porcentagem);
            
        }
        // echo "<h1>".sizeof($porcentagemComissoes)."</h1>";
        if (sizeof($porcentagemComissoes) > 0){
            $porcentagemErrada = true;
            $porcentagemCorreta = 0;
            foreach($porcentagemComissoes as $rs_porcentagem){

                if ($rs_porcentagem['porcentagem'] == $porcentagem && $rs_porcentagem['parcela'] == $parcelaPesquisa){
                    // echo "Comissao esta com a porcentagem certa!";
                    $porcentagemErrada = false;
                }
                if ($rs_porcentagem['parcela'] == $parcelaPesquisa){
                    $porcentagemCorreta = $rs_porcentagem['porcentagem'];
                }
            }
            if ($porcentagemErrada){
                if($log){
                    echo "<p style='background:orange;'>A porcentagem da comissao não está como o esperado! <br>A porcentagem correta seria $porcentagemCorreta%</p>";
                }
            }
        }else{
            if ($log){
                echo "<p style='background:orange;'>A porcentagem da comissao não está como o esperado!</p>";
        
            }
        }


        if ($idOperadora > 0){

            for($j = 0 ; $j <= sizeof($tblContas); $j++){
                $contasRow = $tblContas[$j];
                if ($idOperadora == 8){
                    $operadora = 'NEXT';
                }
                if( $operadora == $contasRow['titulo']){
                    $idOrigem = $contasRow['id'];
                    if ($log){
                        echo "<p> <strong>id_conta: </strong>".$idOrigem."</p>";
                    }
                    break;
                }
                
                # Pesquida para encontrar as contas da AFFINITY
                if( $contaOrigemNome == $contasRow['titulo']){
                    $idOrigem = $contasRow['id'];
                    if ($log){
                        echo "<p> <strong>id_conta: </strong>".$idOrigem."</p>";
                    }
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
                    $valorBrutoComissao = $res['valor'];

                    if ($operadoraFinalizado == 5){
                        $idOrigem = 22;
                    }
                    

                }else{
                    $res = procuraPelaRazaoSocial($nomeContrato, $idOperadora);
                    if($res){
                        $idFinalizado = $res['id'];
                        $portabilidade = $res['portabilidade'];
                        $nomeContrato = $res['razao_social'];
                        $valorBrutoComissao = $res['valor'];
                    }else{
                        $buscaRazaoSocialArray = array();
                        if($idOperadora == 2){
                            // $sql = "select * from tbl_finalizado where razao_social like '%$nomeContrato%' and (id_operadora = $idOperadora or id_operadora = 5);";
                            $sql = "select id,id_operadora, id_tipo_plano, razao_social, n_apolice, valor from tbl_finalizado where replace(replace(replace(replace(replace(razao_social, '-', ''), '.',''), ' ',''), '/',''), '&', '')  like '%".str_replace(' ', '', str_replace("&", "", $nomeContrato))."%' and (id_operadora = $idOperadora or id_operadora = 5);";
                        }else{
                            // $sql = "select * from tbl_finalizado where razao_social like '%$nomeContrato%' and where id_operadora = $idOperadora;";

                            $sql = "select id,id_operadora, id_tipo_plano, razao_social, n_apolice, valor from tbl_finalizado where replace(replace(replace(replace(replace(razao_social, '-', ''), '.',''), ' ',''), '/',''), '&', '')  like '%".str_replace(' ', '', str_replace( "&", "", $nomeContrato))."%' and id_operadora = $idOperadora;";
                        }
                        // echo "<p>$sql</p>";
                        $buscaRazaoSocial = mysqli_query($conect, $sql);
                        while ($rs_busca = mysqli_fetch_array($buscaRazaoSocial)){
                            array_push($buscaRazaoSocialArray, $rs_busca);
                        }

                        if(sizeof($buscaRazaoSocialArray) == 1){
                            $row = $buscaRazaoSocialArray[0];
                            if($row['razao_social'] != "" && $row['razao_social'] != null){
                                $idFinalizado = intval($row['id']);
                                $nomeContrato = $row['razao_social'];
                                $valorBrutoComissao = $row['valor'];
                            }else{
                                array_push($comissoesNaoEncontradas, $comissaoRow);
                            }
                            
                        }

                        
                    }
                }
            }else{
                if ($log){
                    echo "<p style='color:red;'>Não achou a conta de origem!</p>";
                }
            }
            if ($log){
                // echo "<p style='background: yellow;'><strong>Razao Social: </strong>".$nomeContrato."</p>";
                echo "<p><strong>Busca: </strong>$idBuscaComissao (id_busca) - <strong style='background: yellow;'>$nomeContrato</strong> (Nome Contrato) - $contrato (Numero da apolice)</p>";
            }
            

            if ($idFinalizado > 0){
                if ($log){
                echo "<h5> ID: <strong style='color:green;'>ID FINALIZADO: $idFinalizado </strong> encontrou</h5>";
                }

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
                        // echo "<h5><strong style='color:red;'>Falta pagar a ultima parcela lançada pela operadora: ".$key."</strong></h5>";
                    }else{
                        if ($log){
                            echo "<h5><strong style='color:red;'>Falta Pagar a parcela: ".$key." </strong>.</h5>";
                        }
                    }
                }
                
                if ($idOperadora == 1 && $parcela < 4){
                    $valor_calc = $valorBrutoComissao;
                    echo "<p><strong>Valor Bruto Comissao: </strong>R$ ".$valor_calc."</p>";
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
                


                // if($idFinalizado > 0 && $refComissaoPendente){
                //     array_push($comissoesEncontradas, $dadosComissao);
                // }else{

                $ref = false;

                $valor = (float) 0.0;

                // echo "<h1>".sizeof($trasacoes)."</h1>";

                if (sizeof($trasacoes) > 0){
                    $refTransacao = false;
                    // echo json_encode($trasacoes);
                    foreach($trasacoes as $trasacao){
                        // echo $trasacao;
                        
                        if (strval($trasacao['parcela']) == strval($parcela) && strval($trasacao['valor']) == strval($valor_calc)){
                            $refTransacao = true;
                            echo "<p style='background:pink;'>".$trasacao['valor']." - ".$trasacao['parcela'] ." || ".$valor_calc." - ".$parcela ."</p>";
                        }else{
                            echo "<p>".$trasacao['valor']." - ".$trasacao['parcela'] ." || ".$valor_calc." - ".$parcela ."</p>";
                        }
                    }
                    $ref = $refTransacao;
                }else{
                    $ref = false;
                    if ($log){
                    echo "<p>Nao achou nenhum pagamento</p>";
                    }
                }
                

                
                // if(strval($valor) == strval($valor_calc)){
                //     $ref = false;
                // }

                if ($ref){
                    if ($log){
                    echo "<h5><strong style='color:#5da170;'>Já foi paga a parcela: ".$parcela." </strong></h5>";
                    }
                }else{
                    array_push($comissoesEncontradas, $dadosComissao);
                    if ($log){
                    echo "<h5><strong style='color:red;'>=> Falta pagar a ultima parcela lançada pela operadora: ".$parcela." </strong></h5>";
                    }
                }
                // }

            }else{
                array_push($comissoesNaoEncontradas, $comissaoRow);
                if ($log){
                echo "<p style='color:red;'>Não achou a o numero da apólice nem a razao social!</p>";
                }
            }
        }else{
            if ($log){
            echo "<p> Não encontrou a operadora!</p>";
            }
        }

        
    }

    $comissoesEncontradasJson = json_encode($comissoesEncontradas);
    $comissoesNaoEncontradasJson = json_encode($comissoesNaoEncontradas);
    echo "
    <script> 
        let teste1 = $comissoesEncontradasJson ; console.log('Encontrados: ',teste1);
        let teste2 = $comissoesNaoEncontradasJson ; console.log('Nao Encontrados :', teste2);
    </script>";



    
    $comissoesPagarAParte = array();
    foreach($comissoes as $comissao){
        if ($comissao['porcentagem'] < 50 && $comissao['parcela'] < 4){
            array_push($comissoesPagarAParte, $comissao);
        }else{
            array_push($comissaoNew, $comissao);
        }
    }

    # Finalizar o lançamento das comissoes
    $comissoesPagarAParte = array();
    $comissoesNegativas = array();
    if ($salvar){
        $count = 0;
        foreach($comissoesEncontradas as $comissao){
            $count ++;
            // echo json_encode($comissao);
            if ($comissao['valor_calc'] < 0.0){
                array_push($comissoesNegativas, $comissao);
            }else{
                # COLOCAR A PARTE AS COMISSOES QUE TEM INCLUSAO
                if ($comissao['porcentagem'] < 50 && $comissao['parcela'] < 4){
                    // echo "<p>Pagar a parte ".$comissao['razao_social']."</p>";
                    array_push($comissoesPagarAParte, $comissao);
                    lancaComissaoVitaliciaSemDistribuicao($comissao);
                }else{
                    // echo "<p>Pagar a parte ".$comissao['descricao']."</p>";
                    
                    pagarComissoes($comissao);
                }
            }

            
        }
        if ($log){
        echo "<br>$count";
        }
    }

}
processo($comissoes);


function procuraPeloNumeroContratoAtual( $numeroContrato, $idOperadora){
    global $tblFinalizado;

    if ($numeroContrato != "" && $numeroContrato != null){
    
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
    }

    return false;

}

function procuraPelaRazaoSocial( $nomeContrato, $idOperadora){
    global $tblFinalizado;

    if ($nomeContrato != "" && $nomeContrato != null){

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
    }

    return false;
}

mysqli_close($conect);
?>