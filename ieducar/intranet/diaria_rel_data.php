<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	*																	     *
	*	@author Prefeitura Municipal de Itajaí								 *
	*	@updated 29/03/2007													 *
	*   Pacote: i-PLB Software Público Livre e Brasileiro					 *
	*																		 *
	*	Copyright (C) 2006	PMI - Prefeitura Municipal de Itajaí			 *
	*						ctima@itajai.sc.gov.br					    	 *
	*																		 *
	*	Este  programa  é  software livre, você pode redistribuí-lo e/ou	 *
	*	modificá-lo sob os termos da Licença Pública Geral GNU, conforme	 *
	*	publicada pela Free  Software  Foundation,  tanto  a versão 2 da	 *
	*	Licença   como  (a  seu  critério)  qualquer  versão  mais  nova.	 *
	*																		 *
	*	Este programa  é distribuído na expectativa de ser útil, mas SEM	 *
	*	QUALQUER GARANTIA. Sem mesmo a garantia implícita de COMERCIALI-	 *
	*	ZAÇÃO  ou  de ADEQUAÇÃO A QUALQUER PROPÓSITO EM PARTICULAR. Con-	 *
	*	sulte  a  Licença  Pública  Geral  GNU para obter mais detalhes.	 *
	*																		 *
	*	Você  deve  ter  recebido uma cópia da Licença Pública Geral GNU	 *
	*	junto  com  este  programa. Se não, escreva para a Free Software	 *
	*	Foundation,  Inc.,  59  Temple  Place,  Suite  330,  Boston,  MA	 *
	*	02111-1307, USA.													 *
	*																		 *
	* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
require_once ("include/clsBase.inc.php");
require_once ("include/clsCadastro.inc.php");
require_once ("include/relatorio.inc.php");
require_once ("include/Geral.inc.php");


class clsIndex extends clsBase
{
	function Formular()
	{
		$this->SetTitulo( "{$this->_instituicao} Relatório de Diárias" );
		$this->processoAp = "299";
	}
}

class indice extends clsCadastro
{
	var $cod_funcionario;
	var $nome_funcionario;
	var $data_partida;
	var $data_chegada;
	var $data_inicial;
	var $data_final;
	var $valor_total;

	function Inicializar()
	{
		@session_start();
		$this->cod_pessoa_fj = $_SESSION['id_pessoa'];
		session_write_close();
		$retorno = "Novo";
		return $retorno;
	}

	function Gerar()
	{
		$this->campoData("data_inicial", "Data Inicial", $this->data_inicial);
		$this->campoData("data_final", "Data Final", $this->data_final);
	}

	function Novo()
	{
		if ($this->data_inicial != "" || $this->data_final != "")
		{
			$AND = '';
			if ($this->data_inicial)
			{
				$data = explode("/", $this->data_inicial);
				$dia_i = $data[0];
				$mes_i = $data[1];
				$ano_i = $data[2];

				$data_inicial = $ano_i."-".$mes_i."-".$dia_i." 00:00:00";

				$AND = " AND a.data_partida >= '{$data_inicial}'";
			}

			if ($this->data_final)
			{

				$data_ = explode("/", $this->data_final);
				$dia_f = $data_[0];
				$mes_f = $data_[1];
				$ano_f = $data_[2];

				$data_final = $ano_f."-".$mes_f."-".$dia_f." 23:59:59";

				$AND .= " AND a.data_chegada <= '{$data_final}'";
			}

//			if ($data_inicial <= $data_final)
//			{
				$sql = "SELECT a.ref_funcionario, b.nome, a.data_partida, a.data_chegada, sum( COALESCE(vl100,0) + COALESCE(vl75,0) + COALESCE(vl50,0) + COALESCE(vl25,0) ) as valor, a.objetivo, a.destino FROM pmidrh.diaria a, cadastro.pessoa b WHERE a.ref_funcionario = b.idpes {$AND} AND ativo = 't' GROUP BY a.ref_funcionario, b.nome, a.data_partida, a.data_chegada, a.objetivo, a.destino ORDER BY b.nome";

				$relatorio = new relatorios("Relatório de Diárias", 200, false, "SEGPOG - Departamento de Logística", "A4", "Prefeitura de Itajaí\nSEGPOG - Departamento de Logística\nRua Alberto Werner, 100 - Vila Operária\nCEP. 88304-053 - Itajaí - SC");

				//tamanho do retangulo, tamanho das linhas.
				$relatorio->novaPagina();

				$db = new clsBanco();
				$db->Consulta( $sql );
				if( $db->Num_Linhas() )
				{
					$old_funcionario = 0;
					$soma_valores = 0;
					while ( $db->ProximoRegistro() )
					{
						list( $cod_funcionario, $nome_funcionario, $data_partida, $data_chegada, $valor_total, $objetivo, $destino ) = $db->Tupla();

						if ($old_funcionario != $cod_funcionario )
						{
							$relatorio->novalinha( array( "Funcionário: {$nome_funcionario}"), 0, 13, true);
							$old_funcionario = $cod_funcionario;

							$relatorio->novalinha( array( "Data Partida", "Data Chegada", "Valor Total" ) );
						}

						$data_partida = date( "d/m/Y H:i", strtotime( substr($data_partida,0,19) ) );
						$data_chegada = date( "d/m/Y H:i", strtotime( substr($data_chegada,0,19) ) );

						$relatorio->novalinha( array( $data_partida, $data_chegada, number_format($valor_total, 2, ',', '.') ),1,13);
						$relatorio->novalinha( array( "Destino", $destino ) );
						$relatorio->novalinha( array( "Objetivo", $objetivo ) );
						$relatorio->novalinha( array( "" ) );

						$soma_valores += $valor_total;
					}

					$relatorio->novalinha( array( "" ) );
					$relatorio->novalinha( array( "Valor total do periodo:", number_format( $soma_valores, 2, ',', '.' ) ) );

					// pega o link e exibe ele ao usuario
					$link = $relatorio->fechaPdf();
					$this->campoRotulo("arquivo","Arquivo", "<a href='" . $link . "'>Visualizar Relatório</a>");
				}
				else
				{
					$this->campoRotulo("aviso", "Aviso", "Nenhum Funcionário neste relatório.");
				}
			//}
			//else
			//{
			//	$this->campoRotulo("aviso", "Aviso", "Data //Chegada maior que a Data Partida.");
			//}
		}
		else
		{
			$this->campoRotulo("aviso","Aviso", "Preencha os campos.");
		}
	$this->largura = "100%";

	return true;

}

	function Editar()
	{
	}

	function Excluir()
	{
	}
}

$pagina = new clsIndex();

$miolo = new indice();
$pagina->addForm( $miolo );

$pagina->MakeAll();
?>