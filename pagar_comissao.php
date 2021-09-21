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
    // echo "__$sql";

    $result = mysqli_query($conect, $sql) or die(mysqli_error($conect));
    $id_corretor_insert = "";

    if ($rs = mysqli_fetch_array($result)) {
        /******DADOS SOBRE A PROPOSTA*****/

        //Infos úteis do contrato
        $id_tipo_venda = $rs['id_tipo_venda'];
        $id_sindicato = $rs['id_sindicato'];
        $id_operadora = $rs['id_operadora'];
        $id_produtor = $rs['id_produtor'];
        $id_corretor = $rs['id_corretor'];
        $id_companhia = $rs['id_companhia'];
        $id_tipo_adesao = $rs['id_tipo_adesao'];
        $portabilidade = $rs['portabilidade'];
        $data_venda = $rs['data_lancamento'];
        $empresarial = 1;
        $id_treinador = 0;
        $treinador = 0;
        $acompanhado = 0;

        if ($rs['id_companhia'] > 0) {
            $acompanhado = 1;
        }

        if ($id_sindicato > 0) {
            $empresarial = 0;
            if ($portabilidade == 1) {
                $portabilidade = 0;
            } else {
                $portabilidade = 1;
            }
        }


        /*
                        ACERTO DE COMISSIONAMENTO
                        Inicio
                    */

        // PEGANDO PROPOSTAS QUE JÁ FORAM PAGAS DA MESMA DATA DE VENDA DO CORRETOR X
        $sql_acerto = "select group_concat(id separator ', ') as id_vendas from tbl_finalizado as f where 
                    data_lancamento like '" . date("Y-m-", strtotime($data_venda)) . "%' and (id_corretor = '" . $id_corretor . "' or id_companhia = '" . $id_corretor . "' and data_lancamento >= '2021-06-01') and id_tipo_venda = '" . $rs['id_tipo_venda'] . "' and id_status not in (17,19) and (select count(id) from tbl_transacoes where id_finalizado = f.id) > 0;";

        //echo $sql_acerto."<br><br>";

        $result_acerto = mysqli_query($conect, $sql_acerto);

        if ($rs_acerto = mysqli_fetch_array($result_acerto)) {
            $id_vendas = $rs_acerto['id_vendas'];

            // PEGANDO O VALOR BRUTO DE CADA LANCAMENTO DAS VENDAS
            $sql_acerto2 = "select * from tbl_transacoes where id_finalizado in ($id_vendas) and id_destino = 1";
            //echo $sql_acerto2."<br><br>";

            $result_acerto2 = mysqli_query($conect, $sql_acerto2);

            while ($rs_acerto2 = mysqli_fetch_array($result_acerto2)) {

                $id_finalizado_lancamento = $rs_acerto2['id_finalizado'];
                $parcela_lancamento = $rs_acerto2['parcela'];
                $acerto_id_lancamento = $rs_acerto2['id'];
                $valor_bruto_lancamento = $rs_acerto2['valor'];
                $valor_corretor = 0;
                $valor_closer = 0;

                //COLETANDO VALOR DE CORRETOR
                $sql_acerto3 = 'select sum(valor) as valor from tbl_transacoes where id_bruto = ' . $rs_acerto2['id'] . ' and descricao like "%Vendedor%";';
                $result_acerto3 = mysqli_query($conect, $sql_acerto3);

                if ($rs_acerto3 = mysqli_fetch_array($result_acerto3)) {
                    $valor_corretor = $rs_acerto3['valor'];
                }
                //COLETANDO VALOR DE CLOSER
                $sql_acerto4 = 'select sum(valor) as valor from tbl_transacoes where id_bruto = ' . $rs_acerto2['id'] . ' and descricao like "%Closer%";';
                $result_acerto4 = mysqli_query($conect, $sql_acerto4);

                if ($rs_acerto4 = mysqli_fetch_array($result_acerto4)) {
                    $valor_closer = $rs_acerto4['valor'];
                }

                //COLETANDO VALOR DE VENDA CORRETOR
                $acerto_valor_venda_corretor = 0;

                $sql_acerto_corretor = "select group_concat(id separator ', ') as id_vendas, sum(valor) as valor_venda from tbl_finalizado as f where 
                            data_lancamento like '" . date("Y-m-", strtotime($data_venda)) . "%' and (id_corretor = '" . $id_corretor . "' or id_companhia = '" . $id_corretor . "' and data_lancamento >= '2021-06-01') and id_tipo_venda = '" . $id_tipo_venda . "' and id_status not in (17,19) and (select count(id) from tbl_transacoes where id_finalizado = f.id) > 0 or id = '$txt_id_finalizado';";
                //echo $sql_acerto_corretor."<br><br>";

                $result_acerto_corretor = mysqli_query($conect, $sql_acerto_corretor);

                if ($rs_acerto_corretor = mysqli_fetch_array($result_acerto_corretor)) {
                    $acerto_valor_venda_corretor = $rs_acerto_corretor['valor_venda'];
                }

                //COLETANDO VALOR DE VENDA CLOSER
                $acerto_valor_venda_closer = 0;

                $sql_meta_config = "select cmc.* from tbl_usuario as u inner join tbl_config_meta_comissionamento as cmc on cmc.id_tipo_comissao = if(u.id_tipo_comissao = 17, if('$data_venda' >= '2021-05-01', 17, 1), if(u.id_tipo_comissao = 3, if('$data_venda' >= '2020-10-01', 1, 3), u.id_tipo_comissao)) where id_usuario = '" . $id_companhia . "'";
                $result_meta_config = mysqli_query($conect, $sql_meta_config) or die(mysqli_error($conect));
                while ($rs_meta_config = mysqli_fetch_array($result_meta_config)) {

                    $sql2 = "select sum(valor) as valor_venda from tbl_finalizado where 
                                data_lancamento like '" . date("Y-m-", strtotime($data_venda)) . "%' and (id_corretor = '" . $id_companhia . "' or id_companhia = '" . $id_companhia . "' and data_lancamento >= '2021-06-01') and portabilidade = '" . $rs_meta_config['empresarial'] . "' and id_tipo_venda = '" . $rs_meta_config['id_tipo_venda'] . "'
                                or data_lancamento like '" . date("Y-m-", strtotime($data_venda)) . "%' and id_call_center = '" . $id_companhia . "' and portabilidade = '" . $rs_meta_config['empresarial'] . "' and id_tipo_venda = '" . $rs_meta_config['id_tipo_venda'] . "'
                                or data_lancamento like '" . date("Y-m-", strtotime($data_venda)) . "%' and id_supervisor_corretor = '" . $id_companhia . "' and data_pagamento is not null and id_status not in (17,19) and portabilidade = '" . $rs_meta_config['empresarial'] . "' and id_tipo_venda = '" . $rs_meta_config['id_tipo_venda'] . "';";

                    $result2 = mysqli_query($conect, $sql2) or die(mysqli_error($conect));

                    if ($rs2 = mysqli_fetch_array($result2)) {
                        $acerto_valor_venda_closer += $rs2['valor_venda'];
                    }
                }


                //CONFERENCIA DO CORRETOR
                $sql_proposta = "SELECT * FROM tbl_finalizado where id = '$id_finalizado_lancamento'";

                $result_proposta = mysqli_query($conect, $sql_proposta) or die(mysqli_error($conect));

                if ($rs_proposta = mysqli_fetch_array($result_proposta)) {
                    /******DADOS SOBRE A PROPOSTA*****/

                    //Infos úteis do contrato
                    $acerto_id_tipo_venda = $rs_proposta['id_tipo_venda'];
                    $acerto_id_sindicato = $rs_proposta['id_sindicato'];
                    $acerto_id_operadora = $rs_proposta['id_operadora'];
                    $acerto_id_produtor = $rs_proposta['id_produtor'];
                    $acerto_id_corretor = $rs_proposta['id_corretor'];
                    $acerto_id_tipo_adesao = $rs_proposta['id_tipo_adesao'];
                    $acerto_portabilidade = $rs_proposta['portabilidade'];
                    $acerto_data_venda = $rs_proposta['data_lancamento'];
                    $acerto_empresarial = 1;
                    $acerto_id_treinador = 0;
                    $acerto_treinador = 0;
                    $acerto_acompanhado = 0;

                    if ($rs_proposta['id_companhia'] > 0) {
                        $acerto_acompanhado = 1;
                    }

                    if ($acerto_id_sindicato > 0) {
                        $acerto_empresarial = 0;
                        if ($acerto_portabilidade == 1) {
                            $acerto_portabilidade = 0;
                        } else {
                            $acerto_portabilidade = 1;
                        }
                    }

                    //Área do treinador

                    $sql = "select * from tbl_treinador_usuario where id_usuario = '" . $rs_proposta['id_corretor'] . "' and ('$data_venda' between dt_venda_inicio and if(dt_venda_fim is not null, dt_venda_fim, '$data_venda'));";
                    //echo $sql;
                    $result_treinador = mysqli_query($conect, $sql);

                    if ($rs_treinador = mysqli_fetch_array($result_treinador)) {
                        $acerto_treinador = 1;
                        //echo $id_treinador;
                    }

                    $acerto_id_tipo_comissao_corretor = 0;

                    $sql_tipo_comissao = "select u.id_tipo_comissao from tbl_usuario as u where id_usuario = '" . $rs_proposta['id_corretor'] . "'";
                    $result_tipo_comissao = mysqli_query($conect, $sql_tipo_comissao) or die(mysqli_error($conect));
                    if ($rs_tipo_comissao = mysqli_fetch_array($result_tipo_comissao)) {
                        $acerto_id_tipo_comissao_corretor = $rs_tipo_comissao['id_tipo_comissao'];
                    }

                    if ($acerto_id_tipo_comissao_corretor == 3 && $data_venda >= '2020-10-01') {
                        $acerto_id_tipo_comissao_corretor = 1;
                    }

                    if ($acerto_id_produtor == 181) {
                        $acerto_valor_venda_corretor = 31000;
                    }

                    $sql_porcentagem_corretor = "select u.id_tipo_comissao, u.id_tipo_empresa, tcv.* from tbl_usuario as u inner join tbl_tipo_comissao_valor as tcv on tcv.id_tipo_comissao = if(u.id_tipo_comissao = 17, if('$data_venda' >= '2021-05-01', 17, 1), if(u.id_tipo_comissao = 3, if('$data_venda' >= '2020-10-01', 1, 3), u.id_tipo_comissao)) where u.id_usuario = '" . $rs_proposta['id_corretor'] . "' and tcv.parcela = '$parcela_lancamento' and tcv.id_tipo_venda = '$acerto_id_tipo_venda' and tcv.empresarial = '$acerto_empresarial' and tcv.portabilidade = '$acerto_portabilidade' and tcv.corretor = '1' and tcv.produtor = '0' and tcv.acompanhado = '$acerto_acompanhado' and tcv.closer = '0' and tcv.treinador = '$acerto_treinador' and tcv.supervisor_adm = '0' and tcv.account = '0' and tcv.gestor = '0' and tcv.id_tipo_adesao = '$acerto_id_tipo_adesao' and ('$acerto_valor_venda_corretor' between tcv.meta_min and if(tcv.meta_max > 0, tcv.meta_max, '$acerto_valor_venda_corretor')) and ('$data_venda' between if(tcv.dt_inicio is null, '$data_venda', dt_inicio) and if(dt_fim is null, '$data_venda', dt_fim)) and if(tcv.id_tipo_comissao_corretor = 0, '$acerto_id_tipo_comissao_corretor', tcv.id_tipo_comissao_corretor) = '$acerto_id_tipo_comissao_corretor'";

                    $result_porcentagem_corretor = mysqli_query($conect, $sql_porcentagem_corretor);

                    if ($rs_porcentagem_corretor = mysqli_fetch_array($result_porcentagem_corretor)) {
                        //CALCULO DE PORCENTAGEM E CONFERENCIA CORRETOR
                        $porcentagem = $rs_porcentagem_corretor['porcentagem'];

                        if ($acerto_id_operadora == 12 && $parcela_lancamento == 1 && $data_venda <= "2021-03-31") {
                            $sql = "select sum(valor) as valor_venda from tbl_finalizado where 
                                        data_lancamento like '" . date("Y-m-", strtotime($data_venda)) . "%' and id_corretor = '" . $rs_proposta['id_corretor'] . "' and portabilidade = '" . $acerto_portabilidade . "' and id_tipo_venda = '$acerto_id_tipo_venda' and id_status not in (17) and id_operadora = '$acerto_id_operadora';";

                            $result_alt = mysqli_query($conect, $sql) or die(mysqli_error($conect));

                            if ($rs_alt = mysqli_fetch_array($result_alt)) {
                                if ($rs_alt['valor_venda'] > 10000 && $rs_alt['valor_venda'] <= 20000) {
                                    $porcentagem += 10;
                                } else if ($rs_alt['valor_venda'] > 20000) {
                                    $porcentagem += 20;
                                }
                            }
                        } else if ($acerto_id_operadora == 3 && $parcela_lancamento == 1 && $data_venda >= "2021-04-01") {
                            $sql = "select sum(valor) as valor_venda from tbl_finalizado where 
                                        data_lancamento like '" . date("Y-m-", strtotime($data_venda)) . "%' and id_corretor = '" . $rs_proposta['id_corretor'] . "' and portabilidade = '" . $acerto_portabilidade . "' and id_tipo_venda = '$acerto_id_tipo_venda' and id_status not in (17) and id_operadora = '$acerto_id_operadora';";

                            $result_alt = mysqli_query($conect, $sql) or die(mysqli_error($conect));

                            if ($rs_alt = mysqli_fetch_array($result_alt)) {
                                if ($rs_alt['valor_venda'] > 10000 && $rs_alt['valor_venda'] <= 20000) {
                                    $porcentagem += 10;
                                } else if ($rs_alt['valor_venda'] > 20000) {
                                    $porcentagem += 20;
                                }
                            }
                        }

                        $porcentagem = $porcentagem / 100;
                        $valor_calc_liquid = $valor_bruto_lancamento * $porcentagem;
                        $descricao_comissao = $rs_porcentagem_corretor['descricao'];
                        $tipo_empresa = $rs_porcentagem_corretor['id_tipo_empresa'];
                        $irrf = 0;

                        if ($tipo_empresa < 1) {
                            if (!$call_center) {
                                $valor_calc_liquid = $valor_calc_liquid * 0.915;
                            }
                        } else {
                            $irrf = (number_format($valor_calc_liquid, 2, ".", "") - $valor_corretor) * 0.015;
                        }
                        $valor_acerto = number_format($valor_calc_liquid, 2, ".", "") - $valor_corretor;


                        if ($valor_acerto > 0) {
                            //echo "Acerto - " . number_format($valor_acerto, 2, ".", "")." | ";

                            //echo "Valor gerado - " . number_format($valor_calc_liquid, 2, ".", "") . " / Valor pago - " . $valor_corretor."<br><br>";

                            $sql_acerto_conta = "select * from tbl_contas where id_usuario = '" . $rs_proposta['id_corretor'] . "'";
                            $result_acerto_conta = mysqli_query($conect, $sql_acerto_conta);

                            if ($rs_acerto_conta = mysqli_fetch_array($result_acerto_conta)) {
                                $id_conta_insert = $rs_acerto_conta['id'];

                                //echo "<br>".$valor_calc_base." - ".$valor_calc_liquid." - ".$rs['porcentagem']." - ".$rs['imposto']." / ";
                                $transacao = "INSERT INTO tbl_transacoes(data,descricao,id_origem,id_destino,valor, id_finalizado, parcela, vendedor, id_transacao, id_bruto, irrf, id_usuario) values ( '" . date("Y-m-d") . "', '" . $rs_proposta['razao_social'] . " $descricao_comissao' , '1' , '$id_conta_insert' , '" . $valor_acerto . "', '" . $rs_proposta['id'] . "', '$parcela_lancamento', '1', '1', '$acerto_id_lancamento', '$irrf', '0') ";
                                //echo $transacao."<br><br>";
                                //mysqli_query($conect, $transacao) or die(mysqli_error($conect));
                            }
                        } else {
                            //echo "Valor gerado - " . number_format($valor_calc_liquid, 2, ".", "") . " / Valor pago - " . $valor_corretor."<br><br>";
                        }
                    }

                    $sql_porcentagem_closer = "select u.id_tipo_comissao, u.id_tipo_empresa, tcv.* from tbl_usuario as u inner join tbl_tipo_comissao_valor as tcv on tcv.id_tipo_comissao = if(u.id_tipo_comissao = 17, if('$data_venda' >= '2021-05-01', 17, 1), if(u.id_tipo_comissao = 3, if('$data_venda' >= '2020-10-01', 1, 3), u.id_tipo_comissao)) where u.id_usuario = '" . $rs_proposta['id_companhia'] . "' and tcv.parcela = '$parcela_lancamento' and tcv.id_tipo_venda = '$acerto_id_tipo_venda' and tcv.empresarial = '$acerto_empresarial' and tcv.portabilidade = '$acerto_portabilidade' and tcv.corretor = '0' and tcv.produtor = '0' and tcv.acompanhado = '$acerto_acompanhado' and tcv.closer = '1' and tcv.treinador = '$acerto_treinador' and tcv.supervisor_adm = '0' and tcv.account = '0' and tcv.gestor = '0' and tcv.id_tipo_adesao = '$acerto_id_tipo_adesao' and ('$acerto_valor_venda_closer' between tcv.meta_min and if(tcv.meta_max > 0, tcv.meta_max, '$acerto_valor_venda_closer')) and ('$data_venda' between if(tcv.dt_inicio is null, '$data_venda', dt_inicio) and if(dt_fim is null, '$data_venda', dt_fim)) and if(tcv.id_tipo_comissao_corretor = 0, '$acerto_id_tipo_comissao_corretor', tcv.id_tipo_comissao_corretor) = '$acerto_id_tipo_comissao_corretor'";
                    //echo $sql_porcentagem_closer . "<br><br>";
                    $result_porcentagem_closer = mysqli_query($conect, $sql_porcentagem_closer);

                    if ($rs_porcentagem_closer = mysqli_fetch_array($result_porcentagem_closer)) {
                        //CALCULO DE PORCENTAGEM E CONFERENCIA CLOSER
                        $porcentagem = $rs_porcentagem_closer['porcentagem'];

                        $porcentagem = $porcentagem / 100;
                        $valor_calc_liquid = $valor_bruto_lancamento * $porcentagem;
                        $descricao_comissao = $rs_porcentagem_closer['descricao'];
                        $tipo_empresa = $rs_porcentagem_closer['id_tipo_empresa'];
                        $irrf = 0;

                        if ($tipo_empresa < 1) {
                            if (!$call_center) {
                                $valor_calc_liquid = $valor_calc_liquid * 0.915;
                            }
                        } else {
                            $irrf = (number_format($valor_calc_liquid, 2, ".", "") - $valor_closer) * 0.015;
                        }
                        $valor_acerto = number_format($valor_calc_liquid, 2, ".", "") - $valor_closer;


                        if ($valor_acerto > 0) {
                            //echo "(CLOSER) Acerto - " . number_format($valor_acerto, 2, ".", "")." | ";

                            //echo "Valor gerado - " . number_format($valor_calc_liquid, 2, ".", "") . " / Valor pago - " . $valor_closer."<br><br>";

                            $sql_acerto_conta = "select * from tbl_contas where id_usuario = '" . $rs_proposta['id_corretor'] . "'";
                            $result_acerto_conta = mysqli_query($conect, $sql_acerto_conta);

                            if ($rs_acerto_conta = mysqli_fetch_array($result_acerto_conta)) {
                                $id_conta_insert = $rs_acerto_conta['id'];

                                //echo "<br>".$valor_calc_base." - ".$valor_calc_liquid." - ".$rs['porcentagem']." - ".$rs['imposto']." / ";
                                $transacao = "INSERT INTO tbl_transacoes(data,descricao,id_origem,id_destino,valor, id_finalizado, parcela, vendedor, id_transacao, id_bruto, irrf, id_usuario) values ( '" . date("Y-m-d") . "', '" . $rs_proposta['razao_social'] . " $descricao_comissao' , '1' , '$id_conta_insert' , '" . $valor_acerto . "', '" . $rs_proposta['id'] . "', '$parcela_lancamento', '1', '1', '$acerto_id_lancamento', '$irrf', '0') ";
                                //echo $transacao."<br><br>";
                                //mysqli_query($conect, $transacao) or die(mysqli_error($conect));
                            }
                        } else {
                            //echo "(CLOSER) Valor gerado - " . number_format($valor_calc_liquid, 2, ".", "") . " / Valor pago - " . $valor_closer."<br><br>";
                        }
                    }

                    //echo $sql_porcentagem_corretor."<br><br>";
                }
            }
        }
        /*
                        Fim
                    */

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

        if ($id_call_center > 0) {
            $usuario[] = 101;
        }

        //Área do treinador

        $sql = "select * from tbl_treinador_usuario where id_usuario = '" . $rs['id_corretor'] . "' and ('$data_venda' between dt_venda_inicio and if(dt_venda_fim is not null, dt_venda_fim, '$data_venda'));";
        //echo $sql;
        $result_treinador = mysqli_query($conect, $sql);

        if ($rs_treinador = mysqli_fetch_array($result_treinador)) {
            $usuario[] = $rs_treinador['id_treinador'];
            $id_treinador = $rs_treinador['id_treinador'];
            $treinador = 1;
            //echo $id_treinador;
        } else {
            $usuario[] = 0;
        }

        $usuario[] = $rs['id_supervisor_corretor'];

        //Área técnica
        $usuario[] = 26;
        $usuario[] = 60;
        $usuario[] = 142;
        $usuario[] = 143;
        $usuario[] = 144;

        $id_tipo_comissao_corretor = 0;

        $sql = "select u.id_tipo_comissao from tbl_usuario as u where id_usuario = '" . $rs['id_corretor'] . "'";
        $result = mysqli_query($conect, $sql) or die(mysqli_error($conect));
        if ($rs = mysqli_fetch_array($result)) {
            $id_tipo_comissao_corretor = $rs['id_tipo_comissao'];
        }

        if ($id_tipo_comissao_corretor == 3 && $data_venda >= '2020-10-01') {
            $id_tipo_comissao_corretor = 1;
        }

        $contador = 0;
        //print_r($usuario);
        while ($contador < count($usuario)) {
            $valor_venda = 0;

            $sql = "select cmc.* from tbl_usuario as u inner join tbl_config_meta_comissionamento as cmc on cmc.id_tipo_comissao = if(u.id_tipo_comissao = 17, if('$data_venda' >= '2021-05-01', 17, 1), if(u.id_tipo_comissao = 3, if('$data_venda' >= '2020-10-01', 1, 3), u.id_tipo_comissao)) where id_usuario = '" . $usuario[$contador] . "'";
            $result = mysqli_query($conect, $sql) or die(mysqli_error($conect));
            while ($rs = mysqli_fetch_array($result)) {

                $sql2 = "select sum(valor) as valor_venda from tbl_finalizado where 
                            data_lancamento like '" . date("Y-m-", strtotime($data_venda)) . "%' and (id_corretor = '" . $usuario[$contador] . "' or id_companhia = '" . $usuario[$contador] . "' and data_lancamento >= '2021-06-01') and portabilidade = '" . $rs['empresarial'] . "' and id_tipo_venda = '" . $rs['id_tipo_venda'] . "'
                            or data_lancamento like '" . date("Y-m-", strtotime($data_venda)) . "%' and id_call_center = '" . $usuario[$contador] . "' and portabilidade = '" . $rs['empresarial'] . "' and id_tipo_venda = '" . $rs['id_tipo_venda'] . "'
                            or data_lancamento like '" . date("Y-m-", strtotime($data_venda)) . "%' and id_supervisor_corretor = '" . $usuario[$contador] . "' and data_pagamento is not null and id_status not in (17,19) and portabilidade = '" . $rs['empresarial'] . "' and id_tipo_venda = '" . $rs['id_tipo_venda'] . "';";

                if ($contador == 0) {
                    $sql2 = "select group_concat(id separator ', ') as id_vendas, sum(valor) as valor_venda from tbl_finalizado as f where data_lancamento like '" . date("Y-m-", strtotime($data_venda)) . "%' and (id_corretor = '" . $usuario[$contador] . "' or id_companhia = '" . $usuario[$contador] . "' and data_lancamento >= '2021-06-01') and id_tipo_venda = '" . $rs['id_tipo_venda'] . "' and id_status not in (17,19) and (select count(id) from tbl_transacoes where id_finalizado = f.id) > 0 or id = '$txt_id_finalizado';";
                }

                $result2 = mysqli_query($conect, $sql2) or die(mysqli_error($conect));

                if ($rs2 = mysqli_fetch_array($result2)) {
                    $valor_venda += $rs2['valor_venda'];
                }

                if ($contador == 0 && $id_produtor == 181) {
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

            if ($contador == 0) {
                $corretor = 1;
            }

            if ($contador == 1) {
                $produtor = 1;
            }

            if ($contador == 2) {
                $closer = 1;
            }

            if ($contador == 3) {
                $account = 1;
            }

            if ($contador == 4) {
                $administrativo = 1;
            }

            if ($contador == 5) {
                $call_center = 1;
            }

            if ($contador == 6) {
                $gestor = 1;
            }

            if ($contador == 7) {
                $supervisor_adm = 1;
            }

            if ($contador == 8) {
                if ($id_call_center > 0) {
                    $call_center = 1;
                }
            }

            $sql = "select u.id_tipo_comissao, u.id_tipo_empresa, tcv.* from tbl_usuario as u inner join tbl_tipo_comissao_valor as tcv on tcv.id_tipo_comissao = if(u.id_tipo_comissao = 17, if('$data_venda' >= '2021-05-01', 17, 1), if(u.id_tipo_comissao = 3, if('$data_venda' >= '2020-10-01', 1, 3), u.id_tipo_comissao)) where u.id_usuario = '" . $usuario[$contador] . "' and tcv.parcela = '$txt_parcela' and tcv.id_tipo_venda = '$id_tipo_venda' and tcv.empresarial = '$empresarial' and tcv.portabilidade = '$portabilidade' and tcv.corretor = '$corretor' and tcv.produtor = '$produtor' and tcv.acompanhado = '$acompanhado' and tcv.closer = '$closer' and tcv.treinador = '$treinador' and tcv.supervisor_adm = '$supervisor_adm' and tcv.account = '$account' and tcv.gestor = '$gestor' and tcv.id_tipo_adesao = '$id_tipo_adesao' and ('$valor_venda' between tcv.meta_min and if(tcv.meta_max > 0, tcv.meta_max, '$valor_venda')) and ('$data_venda' between if(tcv.dt_inicio is null, '$data_venda', dt_inicio) and if(dt_fim is null, '$data_venda', dt_fim)) and if(tcv.id_tipo_comissao_corretor = 0, '$id_tipo_comissao_corretor', tcv.id_tipo_comissao_corretor) = '$id_tipo_comissao_corretor'";
            // echo "<br><br>".$sql."<br><br>";
            $result = mysqli_query($conect, $sql) or die(mysqli_error($conect));

            if ($rs = mysqli_fetch_array($result)) {
                $porcentagem = $rs['porcentagem'];

                if ($id_operadora == 12 && $contador == 0 && $txt_parcela == 1 && $data_venda <= "2021-03-31") {
                    $sql = "select sum(valor) as valor_venda from tbl_finalizado where 
                                data_lancamento like '" . date("Y-m-", strtotime($data_venda)) . "%' and id_corretor = '" . $usuario[$contador] . "' and portabilidade = '" . $portabilidade . "' and id_tipo_venda = '$id_tipo_venda' and id_status not in (17) and id_operadora = '$id_operadora';";

                    $result_alt = mysqli_query($conect, $sql) or die(mysqli_error($conect));

                    if ($rs_alt = mysqli_fetch_array($result_alt)) {
                        if ($rs_alt['valor_venda'] > 10000 && $rs_alt['valor_venda'] <= 20000) {
                            $porcentagem += 10;
                        } else if ($rs_alt['valor_venda'] > 20000) {
                            $porcentagem += 20;
                        }
                    }
                } else if ($id_operadora == 3 && $contador == 0 && $txt_parcela == 1 && $data_venda >= "2021-04-01") {
                    $sql = "select sum(valor) as valor_venda from tbl_finalizado where 
                                data_lancamento like '" . date("Y-m-", strtotime($data_venda)) . "%' and id_corretor = '" . $usuario[$contador] . "' and portabilidade = '" . $portabilidade . "' and id_tipo_venda = '$id_tipo_venda' and id_status not in (17) and id_operadora = '$id_operadora';";

                    $result_alt = mysqli_query($conect, $sql) or die(mysqli_error($conect));

                    if ($rs_alt = mysqli_fetch_array($result_alt)) {
                        if ($rs_alt['valor_venda'] > 10000 && $rs_alt['valor_venda'] <= 20000) {
                            $porcentagem += 10;
                        } else if ($rs_alt['valor_venda'] > 20000) {
                            $porcentagem += 20;
                        }
                    }
                }

                $porcentagem = $porcentagem / 100;
                $valor_calc_base = $valor_calc * $porcentagem;
                $descricao_comissao = $rs['descricao'];
                $tipo_empresa = $rs['id_tipo_empresa'];

                $sql = "SELECT * FROM tbl_usuario_conta_comissao where id_usuario = '" . $usuario[$contador] . "'";
                $result = mysqli_query($conect, $sql) or die(mysqli_error($conect));

                while ($rs = mysqli_fetch_array($result)) {
                    $id_conta_insert = $rs['id_conta'];
                    $valor_calc_liquid = $valor_calc_base * $rs['porcentagem'] / 100;
                    $irrf = 0;

                    if ($tipo_empresa < 1) {
                        if (!$call_center) {
                            $valor_calc_liquid = $valor_calc_liquid * 0.915;
                        }
                    } else {
                        $irrf = $valor_calc_liquid * 0.015;
                    }

                    //echo "<br>".$valor_calc_base." - ".$valor_calc_liquid." - ".$rs['porcentagem']." - ".$rs['imposto']." / ";

                    if ($corretor == 1) {
                        $transacao = "INSERT INTO tbl_transacoes(data,descricao,id_origem,id_destino,valor, id_finalizado, parcela, vendedor, id_transacao, id_bruto, irrf, id_usuario, dental) values ( '$data', '$descricao $descricao_comissao' , '$id_destino' , '$id_conta_insert' , '" . $valor_calc_liquid . "', '$txt_id_finalizado', '$txt_parcela', '1', '$id_transacao', '$id_bruto', '$irrf', '" . $_SESSION['id-usuario'] . "', '".$refDental."') ;";
                        mysqli_query($conect, $transacao) or die(mysqli_error($conect));
                    } else if ($administrativo && $data_venda >= "2021-04-26") {

                        //Adicionando todos os outros implantadores
                        $sql_adm = "select u.id_usuario, c.id as id_conta from tbl_usuario as u left join tbl_contas as c on c.id_usuario = u.id_usuario where u.id_nivel = '5' and u.disponibilidade = 1 and data_entrada <= '$data_venda';";                        $result_adm = mysqli_query($conect, $sql_adm) or die(mysqli_error($conect));
                        $qtd_pessoas = mysqli_num_rows($result_adm);
                        
                        //echo $sql_adm."<br>";
                        //echo $qtd_pessoas;
                        //Divisao de porcentagem
                        $valor_calc_liquid = $valor_calc_liquid / $qtd_pessoas;

                        while ($rs_adm = mysqli_fetch_array($result_adm)) {
                            $id_conta_insert = $rs_adm['id_conta'];

                            $transacao = "INSERT INTO tbl_transacoes(data,descricao,id_origem,id_destino,valor, id_finalizado, parcela, id_transacao, id_bruto, irrf, id_usuario, dental) values ( '$data', '$descricao $descricao_comissao' , '$id_destino' , '$id_conta_insert' , '" . $valor_calc_liquid . "', '$txt_id_finalizado', '$txt_parcela', '$id_transacao', '$id_bruto', '$irrf', '" . $_SESSION['id-usuario'] . "', '".$refDental."') ;";
                            // echo "$transacao **";
                            mysqli_query($conect, $transacao);
                        }
                    } else {
                        $transacao = "INSERT INTO tbl_transacoes(data, descricao, id_origem, id_destino, valor, id_finalizado, parcela, id_transacao, id_bruto, irrf, id_usuario, dental) values ( '$data', '$descricao $descricao_comissao' , '$id_destino' , '$id_conta_insert' , '" . $valor_calc_liquid . "', '$txt_id_finalizado', '$txt_parcela', '$id_transacao', '$id_bruto', '$irrf', '" . $_SESSION['id-usuario'] . "', '$refDental') ;";
                        // echo "$transacao __";
                        mysqli_query($conect, $transacao) ;
                    }
                }
            }

            $contador++;
        }
    }
}
