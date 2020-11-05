<?php

require_once("includes/config.php");
require_once('includes/functions.php');

function agruparComissoes($array){
    $tblComissoes = $array;
    $log = false;

    $newArrayComissoes = array();
    $processados = array();
    $pesquisa = "";
    $countNewComission = 0;
    // Agrupar as comissoes que tem o mesmo numero da apólice e parcela
    for ($i = 0 ; $i <= sizeof($tblComissoes); $i++ ){

        if (! in_array( $tblComissoes[$i]['contrato_atual']."-".$tblComissoes[$i]['referencia']."-".$tblComissoes[$i]['parcela']."-".$tblComissoes[$i]['porcentagem'], $processados)){
            
            array_push($processados, $tblComissoes[$i]['contrato_atual']."-".$tblComissoes[$i]['referencia']."-".$tblComissoes[$i]['parcela']."-".$tblComissoes[$i]['porcentagem']);
            // $processados[$countNewComission] = $tblComissoes[$i]['contrato_atual']."-".$tblComissoes[$i]['referencia'];

            $occ = 0;
            if ($log){
                echo "<p>----- ".$tblComissoes[$i]['referencia']." -----</p>";
            }

            $comissaoAux = $tblComissoes[$i]['comissao'];
            for ($j = $i ; $j <= sizeof($tblComissoes); $j++ ){

                if ($occ == 1 && ($tblComissoes[$i]['contrato_atual'] == $tblComissoes[$j]['contrato_atual'])
                        && $tblComissoes[$i]['parcela'] == $tblComissoes[$j]['parcela']
                        && $tblComissoes[$i]['porcentagem'] == $tblComissoes[$j]['porcentagem']){
                    if ($log){
                        if($tblComissoes[$i]['referencia'] == "BRADESCO-SAUDE" || $tblComissoes[$i]['referencia'] == "BRADESCO-DENTAL"){
                            echo "<p>".$tblComissoes[$i]['contrato_atual']." - ".$tblComissoes[$i]['parcela']."</p>";
                        }else{
                            echo "<p>".$tblComissoes[$i]['proposta']."</p>";
                        }
                        echo "<p style='color:red;'>".$tblComissoes[$j]['comissao']."</p>";
                    }
                    
                    $tblComissoes[$i]['comissao'] += $tblComissoes[$j]['comissao'];
                }

                if ($occ == 0 && ($tblComissoes[$i]['contrato_atual'] == $tblComissoes[$j]['contrato_atual']) 
                        && $tblComissoes[$i]['parcela'] == $tblComissoes[$j]['parcela']
                        && $tblComissoes[$i]['porcentagem'] == $tblComissoes[$j]['porcentagem']){
                    if ($log){
                        if($tblComissoes[$i]['referencia'] == "BRADESCO-SAUDE" || $tblComissoes[$i]['referencia'] == "BRADESCO-DENTAL"){
                            echo "<p>".$tblComissoes[$i]['contrato_atual']." - ".$tblComissoes[$i]['parcela']."</p>";
                        }else{
                            echo "<p>".$tblComissoes[$i]['proposta']."</p>";
                        }
                        echo "<p style='color:red;'>".$tblComissoes[$i]['comissao']."</p>";
                    }
                    
                    
                    $occ = 1;
                }
            }
            if ($log){
                echo "<p style='color:green;'>".$tblComissoes[$i]['comissao']."</p>";
            }
            array_push($newArrayComissoes, $tblComissoes[$i]);
            $tblComissoes[$i]['comissao'] = $comissaoAux;
            
            $pesquisa = $tblComissoes[$i]['contrato_atual'];
            $countNewComission ++;
        }else{
            if(!($tblComissoes[$i]['referencia'] == "BRADESCO-SAUDE" || $tblComissoes[$i]['referencia'] == "BRADESCO-DENTAL")){
                array_push($newArrayComissoes, $tblComissoes[$i]);

            }
        }
    }
    return $newArrayComissoes;
}


?>