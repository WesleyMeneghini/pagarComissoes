<?php

require_once("includes/config.php");
require_once('includes/functions.php');

function lancaComissaoVitaliciaSemDistribuicao($comissao){
    global $conect;
    $id_busca_comissao = $comissao['idBuscaComissao'];
    $valor_calc = $comissao['valor_calc'];
    $id_origem = $comissao['id_origem'];
    $id_destino = $comissao['id_destino'];
    $id_transacao = 7;
    $descricao = $comissao['descricao'];
    $data = date("Y-m-d");
    $txt_id_finalizado = $comissao['txt_id_finalizado'];
    $txt_parcela = $comissao['txt_parcela'];
    $refDental = $comissao['dental'];
    $porcentagem = $comissao['porcentagem'];

    $valor_calc = preg_replace('/\,/', '.', $valor_calc);
    $valor_calc = preg_replace('/\.(\d{3})/', '$1', $valor_calc);

    if ($refDental){
        $refDental = 1;
    }else{
        $refDental = 0;
    }

    if ($porcentagem == null){
        $porcentagem = 0;
    }

    $insert_transacoes = "INSERT INTO tbl_transacoes
        (data, descricao, id_origem, id_destino, valor, id_finalizado, parcela, id_transacao, dental, porcentagem, id_usuario) values
        ('$data', '$descricao', '$id_origem', '$id_destino', '$valor_calc', '$txt_id_finalizado', '$txt_parcela', '$id_transacao', $refDental, '$porcentagem', 0);";
    // echo $insert_transacoes;
    $res = mysqli_query($conect, $insert_transacoes) or die(mysqli_error($conect));

    if (($id_busca_comissao != null || $id_busca_comissao != "" ) && $res){
        $updateStatus = "UPDATE `busca_comissoes` SET `paga`='1', `id_finalizado`='$txt_id_finalizado' WHERE `id`='$id_busca_comissao';";
        mysqli_query($conect, $updateStatus) or die(mysqli_error($conect));
    }
}

function pagarComissoes($comissao){

    global $conect;
    $id_busca_comissao = $comissao['idBuscaComissao'];
    $valor_calc = $comissao['valor_calc'];
    $id_origem = $comissao['id_origem'];
    $id_destino = $comissao['id_destino'];
    $id_transacao = $comissao['id_transacao'];
    $descricao = $comissao['descricao'];
    $data = date("Y-m-d");
    $txt_id_finalizado = $comissao['txt_id_finalizado'];
    $txt_parcela = $comissao['txt_parcela'];

    $valor_calc = preg_replace('/\,/', '.', $valor_calc);
    $valor_calc = preg_replace('/\.(\d{3})/', '$1', $valor_calc);
    $refDental = $comissao['dental'];
    $porcentagem = $comissao['porcentagem'];

    if ($refDental){
        $refDental = 1;
    }else{
        $refDental = 0;
    }
    if ($porcentagem == null){
        $porcentagem = 0;
    }

    /*
    *
    *
    CÓDIGO NOVO
    *
    *
    */

    $insert_transacoes = "INSERT INTO tbl_transacoes(data,descricao,id_origem,id_destino,valor, id_finalizado, parcela, id_transacao, id_usuario, dental, porcentagem) values ( '$data' , '$descricao' , '$id_origem' , '$id_destino' , '$valor_calc', '$txt_id_finalizado', '$txt_parcela', '$id_transacao', '0', '$refDental', '$porcentagem') ";

    //echo $insert_transacoes;

    $res = mysqli_query($conect, $insert_transacoes) or die(mysqli_error($conect));

    $id_bruto = mysqli_insert_id($conect);

    // Atualizar o status da tabela da busca_comissoes
    if (($id_busca_comissao != null || $id_busca_comissao != "" ) && $res){
        $updateStatus = "UPDATE `busca_comissoes` SET `paga`='1', `id_finalizado`='$txt_id_finalizado' WHERE `id`='$id_busca_comissao';";
        mysqli_query($conect, $updateStatus) or die(mysqli_error($conect));
    }

    $sql = "SELECT * FROM tbl_finalizado where id = '$txt_id_finalizado'";

    $result = mysqli_query($conect, $sql) or die(mysqli_error($conect));
    $id_corretor_insert = "";

    if ($rs = mysqli_fetch_array($result)){
        /******DADOS SOBRE A PROPOSTA*****/

        //Infos úteis do contrato
        $id_tipo_venda = $rs['id_tipo_venda'];
        $id_sindicato = $rs['id_sindicato'];
        $portabilidade = $rs['portabilidade'];
        $data_venda = $rs['data_lancamento'];
        $call_center = 0;
        $empresarial = 1;

        //Usuários base
        $usuario[] = $rs['id_corretor'];
        $usuario[] = $rs['id_produtor'];
        $usuario[] = $rs['id_companhia'];
        $usuario[] = $rs['id_account'];
        $usuario[] = $rs['id_implantador'];
        $usuario[] = $rs['id_call_center'];

        //Área técnica
        $usuario[] = 26;
        $usuario[] = 60;

        $id_call_center = $rs['id_call_center'];

        //Supervisores        
        $usuario[] = $rs['id_supervisor'];

        if ($id_call_center > 0){
            $usuario[] = 101;
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

        $id_tipo_comissao_corretor = 0;

        $sql = "select u.id_tipo_comissao from tbl_usuario as u where id_usuario = '".$rs['id_corretor']."'";
        $result = mysqli_query($conect, $sql) or die(mysqli_error($conect));
        if ($rs = mysqli_fetch_array($result)){
            $id_tipo_comissao_corretor = $rs['id_tipo_comissao'];
        }
        
        if ($id_tipo_comissao_corretor == 3 && $data_venda >= '2020-11-01'){
            $id_tipo_comissao_corretor = 1;
        }

        $contador = 0;
        //print_r($usuario);
        while ($contador < count($usuario)){
            $valor_venda = 0;

            $sql = "select cmc.* from tbl_usuario as u inner join tbl_config_meta_comissionamento as cmc on cmc.id_tipo_comissao = if(u.id_tipo_comissao = 3, if('$data_venda' >= '2020-11-01', 1, 3), u.id_tipo_comissao) where id_usuario = '".$usuario[$contador]."'";
            $result = mysqli_query($conect, $sql) or die(mysqli_error($conect));
            while ($rs = mysqli_fetch_array($result)){

                $sql2 = "select sum(valor) as valor_venda from tbl_finalizado where 
                data_lancamento like '".date("Y-m-", strtotime($data_venda))."%' and id_corretor = '".$usuario[$contador]."' and portabilidade = '".$rs['empresarial']."' and id_tipo_venda = '".$rs['id_tipo_venda']."'
                or data_lancamento like '".date("Y-m-", strtotime($data_venda))."%' and id_call_center = '".$usuario[$contador]."' and portabilidade = '".$rs['empresarial']."' and id_tipo_venda = '".$rs['id_tipo_venda']."'
                or data_lancamento like '".date("Y-m-", strtotime($data_venda))."%' and id_supervisor_corretor = '".$usuario[$contador]."' and portabilidade = '".$rs['empresarial']."' and id_tipo_venda = '".$rs['id_tipo_venda']."';";

                if ($contador == 0){
                    $sql2 = "select sum(valor) as valor_venda from tbl_finalizado where 
                    data_lancamento like '".date("Y-m-", strtotime($data_venda))."%' and id_corretor = '".$usuario[$contador]."' and portabilidade = '".$rs['empresarial']."' and id_tipo_venda = '".$rs['id_tipo_venda']."';";
                }

                $result2 = mysqli_query($conect, $sql2) or die(mysqli_error($conect));

                if ($rs2 = mysqli_fetch_array($result2)){
                    $valor_venda += $rs2['valor_venda'];
                }
            }

            $corretor = 0;
            $produtor = 0;
            $closer = 0;
            $account = 0;
            $supervisor_adm = 0;

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

            if ($contador == 7){
                $supervisor_adm = 1;
            }

            $sql = "select u.id_tipo_comissao, u.id_tipo_empresa, tcv.* from tbl_usuario as u inner join tbl_tipo_comissao_valor as tcv on tcv.id_tipo_comissao = if(u.id_tipo_comissao = 3, if('$data_venda' >= '2020-11-01', 1, 3), u.id_tipo_comissao) where u.id_usuario = '".$usuario[$contador]."' and tcv.parcela = '$txt_parcela' and tcv.id_tipo_venda = '$id_tipo_venda' and tcv.call_center = '$call_center' and tcv.empresarial = '$empresarial' and tcv.portabilidade = '$portabilidade' and tcv.corretor = '$corretor' and tcv.produtor = '$produtor' and tcv.closer = '$closer' and tcv.supervisor_adm = '$supervisor_adm' and tcv.account = '$account' and ('$valor_venda' between tcv.meta_min and if(tcv.meta_max > 0, tcv.meta_max, '$valor_venda')) and if(tcv.id_tipo_comissao_corretor = 0, '$id_tipo_comissao_corretor', tcv.id_tipo_comissao_corretor) = '$id_tipo_comissao_corretor'";
            $result = mysqli_query($conect, $sql) or die(mysqli_error($conect));

            if ($rs = mysqli_fetch_array($result)){
                $porcentagem = $rs['porcentagem'] / 100;
                $valor_calc_base = $valor_calc * $porcentagem;
                $descricao_comissao = $rs['descricao'];
                $tipo_empresa = $rs['id_tipo_empresa'];

                $sql = "SELECT * FROM tbl_usuario_conta_comissao where id_usuario = '".$usuario[$contador]."'";
                $result = mysqli_query($conect, $sql) or die(mysqli_error($conect));

                while ($rs = mysqli_fetch_array($result)){
                    $id_conta_insert = $rs['id_conta'];
                    $valor_calc_liquid = $valor_calc_base * $rs['porcentagem'] / 100;
                    $irrf = 0;
                    
                    if ($tipo_empresa < 2){
                        $valor_calc_liquid = $valor_calc_liquid * 0.915;
                    }else{
                        $irrf = $valor_calc_liquid * 0.015;
                        $valor_calc_liquid = $valor_calc_liquid * 0.985;
                    }

                    //echo "<br>".$valor_calc_base." - ".$valor_calc_liquid." - ".$rs['porcentagem']." - ".$rs['imposto']." / ";

                    if ($corretor == 1){
                        $transacao = "INSERT INTO tbl_transacoes(data,descricao,id_origem,id_destino,valor, id_finalizado, parcela, vendedor, id_transacao, id_bruto, irrf, dental, id_usuario) values ( '$data', '$descricao $descricao_comissao' , '$id_destino' , '$id_conta_insert' , '".$valor_calc_liquid."', '$txt_id_finalizado', '$txt_parcela', '1', '$id_transacao', '$id_bruto', '$irrf', '$refDental', '0') ";
                        mysqli_query($conect, $transacao) or die(mysqli_error($conect));
                    }else{
                        $transacao = "INSERT INTO tbl_transacoes(data,descricao,id_origem,id_destino,valor, id_finalizado, parcela, id_transacao, id_bruto, irrf, dental, id_usuario) values ( '$data', '$descricao $descricao_comissao' , '$id_destino' , '$id_conta_insert' , '".$valor_calc_liquid."', '$txt_id_finalizado', '$txt_parcela', '$id_transacao', '$id_bruto', '$irrf', '$refDental', '0') ";
                        mysqli_query($conect, $transacao) or die(mysqli_error($conect));
                    }
                }
            }
            $contador++;
        }
    }


    
}
