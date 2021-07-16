<?php

// require_once("includes/config.php");
// require_once('includes/functions.php');

$data = date("Y-m-d");

function lancaComissaoVitaliciaSemDistribuicao($comissao)
{
    // echo "vitalicio";
    global $conect;
    global $data;
    $_SESSION['id-usuario'] = 0;

    $id_busca_comissao = $comissao['idBuscaComissao'];
    $valor_calc = $comissao['valor_calc'];
    $id_origem = $comissao['id_origem'];
    $id_destino = $comissao['id_destino'];
    $id_transacao = 7;
    $descricao = $comissao['descricao'];
    $txt_id_finalizado = $comissao['txt_id_finalizado'];
    $txt_parcela = $comissao['txt_parcela'];
    $refDental = $comissao['dental'];
    $porcentagem = $comissao['porcentagem'];
    $dataPagamentoOperadora = $comissao['dataPagamento'];

    $valor_calc = preg_replace('/\,/', '.', $valor_calc);
    $valor_calc = preg_replace('/\.(\d{3})/', '$1', $valor_calc);

    if ($refDental) {
        $refDental = 1;
    } else {
        $refDental = 0;
    }

    if ($porcentagem == null) {
        $porcentagem = 0;
    }

    $insert_transacoes = "INSERT INTO tbl_transacoes
        (data, descricao, id_origem, id_destino, valor, id_finalizado, parcela, id_transacao, dental, porcentagem, id_usuario, data_pagamento_operadora) values
        ('$data', '$descricao', '$id_origem', '$id_destino', '$valor_calc', '$txt_id_finalizado', '$txt_parcela', '$id_transacao', $refDental, '$porcentagem', '" . $_SESSION['id-usuario'] . "', '$dataPagamentoOperadora');";
    // echo $insert_transacoes;
    $res = mysqli_query($conect, $insert_transacoes) or die(mysqli_error($conect));

    if (($id_busca_comissao != null || $id_busca_comissao != "") && $res) {
        $updateStatus = "UPDATE 
                            `busca_comissoes` 
                        SET 
                            `paga`='1', 
                            `id_finalizado`= '$txt_id_finalizado', 
                            `parcela`= $txt_parcela,
                            `id_conta`= $id_origem
                        WHERE 
                        `id`='$id_busca_comissao';";
        mysqli_query($conect, $updateStatus) or die(mysqli_error($conect));
    }
}

function pagarComissoes($comissao)
{
    // echo "distribuir";
    global $conect;
    global $data;
    $_SESSION['id-usuario'] = 0;


    $id_busca_comissao = $comissao['idBuscaComissao'];
    $valor_calc = $comissao['valor_calc'];
    $id_origem = $comissao['id_origem'];
    $id_destino = $comissao['id_destino'];
    $id_transacao = $comissao['id_transacao'];
    $descricao = $comissao['descricao'];
    $txt_id_finalizado = $comissao['txt_id_finalizado'];
    $txt_parcela = $comissao['txt_parcela'];
    $dataPagamentoOperadora = $comissao['dataPagamento'];

    $valor_calc = preg_replace('/\,/', '.', $valor_calc);
    $valor_calc = preg_replace('/\.(\d{3})/', '$1', $valor_calc);
    $refDental = $comissao['dental'];
    $porcentagem = $comissao['porcentagem'];

    if ($refDental) {
        $refDental = 1;
    } else {
        $refDental = 0;
    }
    if ($porcentagem == null) {
        $porcentagem = 0;
    }



    /*
    *
    *
    CÓDIGO NOVO
    *
    *
    */

    $insert_transacoes = "INSERT INTO tbl_transacoes 
    (data, descricao, id_origem,id_destino,valor, id_finalizado, parcela, id_transacao, id_usuario, dental, porcentagem, data_pagamento_operadora) values 
    ( '$data' , '$descricao' , '$id_origem' , '$id_destino' , '$valor_calc', '$txt_id_finalizado', '$txt_parcela', '$id_transacao', '" . $_SESSION['id-usuario'] . "', '$refDental', '$porcentagem', '$dataPagamentoOperadora') ";

    // echo $insert_transacoes;

    $res = mysqli_query($conect, $insert_transacoes) or die(mysqli_error($conect));

    $id_bruto = mysqli_insert_id($conect);

    // Atualizar o status da tabela da busca_comissoes
    if (($id_busca_comissao != null || $id_busca_comissao != "") && $res) {
        $updateStatus = "UPDATE 
                            `busca_comissoes` 
                        SET 
                            `paga`='1', 
                            `id_finalizado`= '$txt_id_finalizado', 
                            `parcela`= $txt_parcela,
                            `id_conta`= $id_origem
                        WHERE 
                        `id`='$id_busca_comissao';";
        mysqli_query($conect, $updateStatus) or die(mysqli_error($conect));
    }

    $sql = "SELECT * FROM tbl_finalizado where id = '$txt_id_finalizado'";
    echo "__$sql";

    $result = mysqli_query($conect, $sql) or die(mysqli_error($conect));

    if ($rs = mysqli_fetch_array($result)){
        /******DADOS SOBRE A PROPOSTA*****/

        //Infos úteis do contrato
        $id_tipo_venda = $rs['id_tipo_venda'];
        $id_sindicato = $rs['id_sindicato'];
        $id_operadora = $rs['id_operadora'];
        $id_produtor = $rs['id_produtor'];
        $id_tipo_adesao = $rs['id_tipo_adesao'];
        $portabilidade = $rs['portabilidade'];
        $data_venda = $rs['data_lancamento'];
        $empresarial = 1;
        $id_treinador = 0;
        $treinador = 0;

        //Usuários base
        $usuario[] = $rs['id_corretor'];
        $usuario[] = $rs['id_produtor'];
        $usuario[] = $rs['id_companhia'];
        $usuario[] = $rs['id_account'];
        $usuario[] = $rs['id_implantador'];
        $usuario[] = $rs['id_call_center'];
        $usuario[] = $rs['id_gestor'];

        $id_call_center = $rs['id_call_center'];

        //Supervisores        
        $usuario[] = $rs['id_supervisor'];

        if ($id_call_center > 0){
            $usuario[] = 101;
        }
        
        //Área do treinador
        
        $sql = "select * from tbl_treinador_usuario where id_usuario = '" . $rs['id_corretor'] . "' and ('$data_venda' between dt_venda_inicio and if(dt_venda_fim is not null, dt_venda_fim, '$data_venda'));";
        //echo $sql;
        $result_treinador = mysqli_query($conect, $sql);
        
        if ($rs_treinador = mysqli_fetch_array($result_treinador)){
            $usuario[] = $rs_treinador['id_treinador'];
            $id_treinador = $rs_treinador['id_treinador'];
            $treinador = 1;
            //echo $id_treinador;
        }else{
            $usuario[] = 0;
        }

        if ($id_sindicato > 0){
            $empresarial = 0;
            if ($portabilidade == 1){
                $portabilidade = 0;
            }else{
                $portabilidade = 1;
            }
        }

        $usuario[] = $rs['id_supervisor_corretor'];
        
        //Área técnica
        $usuario[] = 26;
        $usuario[] = 60;
        $usuario[] = 142;
        $usuario[] = 143;
        $usuario[] = 144;

        $id_tipo_comissao_corretor = 0;

        $sql = "select u.id_tipo_comissao from tbl_usuario as u where id_usuario = '".$rs['id_corretor']."'";
        $result = mysqli_query($conect, $sql) or die(mysqli_error($conect));
        if ($rs = mysqli_fetch_array($result)){
            $id_tipo_comissao_corretor = $rs['id_tipo_comissao'];
        }
        
        if ($id_tipo_comissao_corretor == 3 && $data_venda >= '2020-10-01'){
            $id_tipo_comissao_corretor = 1;
        }

        $contador = 0;
        //print_r($usuario);
        while ($contador < count($usuario)){
            $valor_venda = 0;

            $sql = "select cmc.* from tbl_usuario as u inner join tbl_config_meta_comissionamento as cmc on cmc.id_tipo_comissao = if(u.id_tipo_comissao = 17, if('$data_venda' >= '2021-05-01', 17, 1), if(u.id_tipo_comissao = 3, if('$data_venda' >= '2020-10-01', 1, 3), u.id_tipo_comissao)) where id_usuario = '".$usuario[$contador]."'";
            $result = mysqli_query($conect, $sql) or die(mysqli_error($conect));
            while ($rs = mysqli_fetch_array($result)){

                $sql2 = "select sum(valor) as valor_venda from tbl_finalizado where 
                data_lancamento like '".date("Y-m-", strtotime($data_venda))."%' and id_corretor = '".$usuario[$contador]."' and portabilidade = '".$rs['empresarial']."' and id_tipo_venda = '".$rs['id_tipo_venda']."'
                or data_lancamento like '".date("Y-m-", strtotime($data_venda))."%' and id_call_center = '".$usuario[$contador]."' and portabilidade = '".$rs['empresarial']."' and id_tipo_venda = '".$rs['id_tipo_venda']."'
                or data_lancamento like '".date("Y-m-", strtotime($data_venda))."%' and id_supervisor_corretor = '".$usuario[$contador]."' and id_status not in (17,19) and portabilidade = '".$rs['empresarial']."' and id_tipo_venda = '".$rs['id_tipo_venda']."';";

                if ($contador == 0){
                    $sql2 = "select sum(valor) as valor_venda from tbl_finalizado where 
                    data_lancamento like '".date("Y-m-", strtotime($data_venda))."%' and id_corretor = '".$usuario[$contador]."' and portabilidade = '".$rs['empresarial']."' and id_tipo_venda = '".$rs['id_tipo_venda']."' and id_status not in (17,19);";
                }

                $result2 = mysqli_query($conect, $sql2) or die(mysqli_error($conect));

                if ($rs2 = mysqli_fetch_array($result2)){
                    $valor_venda += $rs2['valor_venda'];
                }
                
                if ($contador == 0 && $id_produtor == 181){
                    $valor_venda = 31000;
                }
            }

            $corretor = 0;
            $produtor = 0;
            $administrativo = 0;
            $call_center = 0;
            $closer = 0;
            $account = 0;
            $supervisor_adm = 0;
            $gestor = 0;

            if ($contador == 0){
                $corretor = 1;
            }

            if ($contador == 1){
                $produtor = 1;
            }

            if ($contador == 2){
                $closer = 1;
            }

            if ($contador == 3){
                $account = 1;
            }

            if ($contador == 4){
                $administrativo = 1;
            }

            if ($contador == 5){
                $call_center = 1;
            }

            if ($contador == 6){
                $gestor = 1;
            }
            
            if ($contador == 7){
                $supervisor_adm = 1;
            }

            if ($contador == 8){
                if ($id_call_center > 0){
                    $call_center = 1;
                }
            }
            
            if ($id_treinador > 0){
                if ($id_call_center > 0){
                    if ($contador == 9){
                        $treinador = 1;      
                    }
                }else{
                    if ($contador == 8){
                        $treinador = 1;           
                    }
                }
            }

            $sql = "select u.id_tipo_comissao, u.id_tipo_empresa, tcv.* from tbl_usuario as u inner join tbl_tipo_comissao_valor as tcv on tcv.id_tipo_comissao = if(u.id_tipo_comissao = 17, if('$data_venda' >= '2021-05-01', 17, 1), if(u.id_tipo_comissao = 3, if('$data_venda' >= '2020-10-01', 1, 3), u.id_tipo_comissao)) where u.id_usuario = '".$usuario[$contador]."' and tcv.parcela = '$txt_parcela' and tcv.id_tipo_venda = '$id_tipo_venda' and tcv.empresarial = '$empresarial' and tcv.portabilidade = '$portabilidade' and tcv.corretor = '$corretor' and tcv.produtor = '$produtor' and tcv.closer = '$closer' and tcv.treinador = '$treinador' and tcv.supervisor_adm = '$supervisor_adm' and tcv.account = '$account' and tcv.gestor = '$gestor' and tcv.id_tipo_adesao = '$id_tipo_adesao' and ('$valor_venda' between tcv.meta_min and if(tcv.meta_max > 0, tcv.meta_max, '$valor_venda')) and ('$data_venda' between if(tcv.dt_inicio is null, '$data_venda', dt_inicio) and if(dt_fim is null, '$data_venda', dt_fim)) and if(tcv.id_tipo_comissao_corretor = 0, '$id_tipo_comissao_corretor', tcv.id_tipo_comissao_corretor) = '$id_tipo_comissao_corretor'";
            $result = mysqli_query($conect, $sql) or die(mysqli_error($conect));

            if ($rs = mysqli_fetch_array($result)){
                $porcentagem = $rs['porcentagem'];
                
                if ($id_operadora == 12 && $contador == 0 && $txt_parcela == 1 && $data_venda <= "2021-03-31"){                                
                    $sql = "select sum(valor) as valor_venda from tbl_finalizado where 
                    data_lancamento like '".date("Y-m-", strtotime($data_venda))."%' and id_corretor = '".$usuario[$contador]."' and portabilidade = '".$portabilidade."' and id_tipo_venda = '$id_tipo_venda' and id_status not in (17) and id_operadora = '$id_operadora';";
                    
                    $result_alt = mysqli_query($conect, $sql) or die(mysqli_error($conect));

                    if ($rs_alt = mysqli_fetch_array($result_alt)){
                        if ($rs_alt['valor_venda'] > 10000 && $rs_alt['valor_venda'] <= 20000){
                            $porcentagem += 10;
                        }else if ($rs_alt['valor_venda'] > 20000){
                            $porcentagem += 20;
                        }
                    }
                }else if ($id_operadora == 3 && $contador == 0 && $txt_parcela == 1 && $data_venda >= "2021-04-01"){                                
                    $sql = "select sum(valor) as valor_venda from tbl_finalizado where 
                    data_lancamento like '".date("Y-m-", strtotime($data_venda))."%' and id_corretor = '".$usuario[$contador]."' and portabilidade = '".$portabilidade."' and id_tipo_venda = '$id_tipo_venda' and id_status not in (17) and id_operadora = '$id_operadora';";
                    
                    $result_alt = mysqli_query($conect, $sql) or die(mysqli_error($conect));

                    if ($rs_alt = mysqli_fetch_array($result_alt)){
                        if ($rs_alt['valor_venda'] > 10000 && $rs_alt['valor_venda'] <= 20000){
                            $porcentagem += 10;
                        }else if ($rs_alt['valor_venda'] > 20000){
                            $porcentagem += 20;
                        }
                    }
                }
                
                $porcentagem = $porcentagem / 100;
                $valor_calc_base = $valor_calc * $porcentagem;
                $descricao_comissao = $rs['descricao'];
                $tipo_empresa = $rs['id_tipo_empresa'];

                $sql = "SELECT * FROM tbl_usuario_conta_comissao where id_usuario = '".$usuario[$contador]."'";
                $result = mysqli_query($conect, $sql) or die(mysqli_error($conect));

                while ($rs = mysqli_fetch_array($result)){
                    $id_conta_insert = $rs['id_conta'];
                    $valor_calc_liquid = $valor_calc_base * $rs['porcentagem'] / 100;
                    $irrf = 0;
                    
                    if ($tipo_empresa < 1){
                        if (!$call_center){
                            $valor_calc_liquid = $valor_calc_liquid * 0.915;
                        }
                    }else{
                        $irrf = $valor_calc_liquid * 0.015;
                    }

                    //echo "<br>".$valor_calc_base." - ".$valor_calc_liquid." - ".$rs['porcentagem']." - ".$rs['imposto']." / ";

                    if ($corretor == 1) {
                        $transacao = "INSERT INTO tbl_transacoes(data,descricao,id_origem,id_destino,valor, id_finalizado, parcela, vendedor, id_transacao, id_bruto, irrf, id_usuario, dental) values ( '$data', '$descricao $descricao_comissao' , '$id_destino' , '$id_conta_insert' , '" . $valor_calc_liquid . "', '$txt_id_finalizado', '$txt_parcela', '1', '$id_transacao', '$id_bruto', '$irrf', '" . $_SESSION['id-usuario'] . "', '".$refDental."') ;";
                        mysqli_query($conect, $transacao) or die(mysqli_error($conect));
                    }else if ($administrativo && $data_venda >= "2021-04-26"){
                        
                        //Adicionando todos os outros implantadores
                        $sql_adm = "select u.id_usuario, c.id as id_conta from tbl_usuario as u left join tbl_contas as c on c.id_usuario = u.id_usuario where u.id_nivel = '5' and u.disponibilidade = 1;";
                        $result_adm = mysqli_query($conect, $sql_adm) or die(mysqli_error($conect));
                        $qtd_pessoas = mysqli_num_rows($result_adm);
                        
                        //echo $sql_adm."<br>";
                        //echo $qtd_pessoas;
                        //Divisao de porcentagem
                        $valor_calc_liquid = $valor_calc_liquid / $qtd_pessoas;
                        
                        while ($rs_adm = mysqli_fetch_array($result_adm)){
                            $id_conta_insert = $rs_adm['id_conta'];
                            
                            $transacao = "INSERT INTO tbl_transacoes(data,descricao,id_origem,id_destino,valor, id_finalizado, parcela, id_transacao, id_bruto, irrf, id_usuario, dental) values ( '$data', '$descricao $descricao_comissao' , '$id_destino' , '$id_conta_insert' , '" . $valor_calc_liquid . "', '$txt_id_finalizado', '$txt_parcela', '$id_transacao', '$id_bruto', '$irrf', '" . $_SESSION['id-usuario'] . "', '".$refDental."') ";
                            // echo "$transacao **";
                            mysqli_query($conect, $transacao) or die(mysqli_error($conect));
                        }
                    } else {
                        $transacao = "INSERT INTO tbl_transacoes(data, descricao, id_origem, id_destino, valor, id_finalizado, parcela, id_transacao, id_bruto, irrf, id_usuario, dental) values ( '$data', '$descricao $descricao_comissao' , '$id_destino' , '$id_conta_insert' , '" . $valor_calc_liquid . "', '$txt_id_finalizado', '$txt_parcela', '$id_transacao', '$id_bruto', '$irrf', '" . $_SESSION['id-usuario'] . "', '$refDental') ";

                        mysqli_query($conect, $transacao) or die(mysqli_error($conect));
                    }
                }
            }

            $contador++;
        }
    }
}
