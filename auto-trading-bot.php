#!/usr/bin/php
<?php
include ("vendor/autoload.php");
require ("config.php");

//Si no recibe la moneda a operar pregunta por ella
if (@$argv[1]=="")
	{
		ColorTextoConsola("blue");
		echo "MONEDAS CON CONFIGURACIONES DISPONIBLES:\n\r   BCN = ByteCoin\n\r   ETH = Ethereum\n\r   XMR = Monero\n\r   LTC = LiteCoin\n\r   SC = SiaCoin\n\r";
		ColorTextoConsola("green");
		$MonedaSeleccionadaOperacion = readline("Indique su eleccion: ");
		echo 'Seleccionando configuraciones para: '.$MonedaSeleccionadaOperacion;
		ColorTextoConsola();
	}
else
	$MonedaSeleccionadaOperacion=$argv[1];

//DEFINICIONES DE LA OPERACION
//Configuraciones generales del Bot
$AnchoConsola=80;									//Tamano deseado para entornos de ejecucion sobre consolas de texto
$IntervaloEjecucion=10;								//En segundos, tiempo de descanso del Bot antes de buscar nuevamente los cambios del mercado
$MonedaValida=0;									//Asume que se debe ingresar una moneda para operar

if ($MonedaSeleccionadaOperacion=="BCN")
	{
		$MonedaValida=1;
		$MonedaOperar="BCN";								//Codigo de la moneda que se desea operar
		$DivisaComparadoraMercado="BCNBTC";  				//Codigo de moneda mediante el cual se encuentra su comparacion frente a otra divisa
		$DivisaDeSoporte="BTC";  							//Codigo de la moneda principal utilizada para comprar mas divisas de la moneda a operar 
		$DecimalesPrecision=10;								//Cantidad de decimales utilizada para la precision de la moneda a comerciar
		$SensibilidadMercado=".000000003";  				//Indica el valor sensible que debe ser superado por la diferencia entre el precio de venta y de compra actual de la moneda para lanzar una operacion
		$CambioOfertaMercado=".0000000001";					//Valor utilizado para sumar o restar a los precios de compra y venta actuales y cambiar asi la oferta y demanda del mercado con cada orden nueva
		$ComisionOperador="0.1";  							//En porcentaje (Ej: 0.1 = 0.1%) indica la comision que sera descontada por el operador cuando se llene la orden
		$SaldoMinimoSoporte="0.0002";						//Saldo minimo que se debe tener en la divisa de soporte para comprar mas divisas de las que se desea operar
		$SaldoResidualSoporte=".0001";						//Si despues de realizar una operacion de trading se obtiene este excedente residual lo transfiere a la cuenta principal para asegurarlo
		$TamanoBloqueTrading="3";							//Cantidad de bloques de divisas a negociar por cada interaccion.  Es un multiplicador con $SaldoMinimoTrading.  
															//Ej:  Para realizar una operacion de venta de un bloque (1) BCN se requieren minimo (100) BCN.  Es el bloque minimo a negociar
															//	   Si se va entonces a negociar bloques de 2 entonces se debe subir a 200 el $SaldoMinimoTrading
		$SaldoMinimoTrading=$TamanoBloqueTrading*100;		//Cantidad minima que se debe tener de la divisa a negociar para poder solicitar una venta de esta.  ESTA FORMULA PODRIA CAMBIAR POR CADA DIVISA!!!
	}

if ($MonedaSeleccionadaOperacion=="ETH")
	{
		$MonedaValida=1;
		$MonedaOperar="ETH";								//Codigo de la moneda que se desea operar
		$DivisaComparadoraMercado="ETHBTC";  				//Codigo de moneda mediante el cual se encuentra su comparacion frente a otra divisa
		$DivisaDeSoporte="BTC";  							//Codigo de la moneda principal utilizada para comprar mas divisas de la moneda a operar 
		$DecimalesPrecision=6;								//Cantidad de decimales utilizada para la precision de la moneda a comerciar
		$SensibilidadMercado=".000001";  						//Indica el valor sensible que debe ser superado por la diferencia entre el precio de venta y de compra actual de la moneda para lanzar una operacion
		$CambioOfertaMercado=".0001";						//Valor utilizado para sumar o restar a los precios de compra y venta actuales y cambiar asi la oferta y demanda del mercado con cada orden nueva
		$ComisionOperador="0.1";  							//En porcentaje (Ej: 0.1 = 0.1%) indica la comision que sera descontada por el operador cuando se llene la orden
		$SaldoMinimoSoporte="0.0001";						//Saldo minimo que se debe tener en la divisa de soporte para comprar mas divisas de las que se desea operar
		$SaldoResidualSoporte=".00001";						//Si despues de realizar una operacion de trading se obtiene este excedente residual lo transfiere a la cuenta principal para asegurarlo
		$TamanoBloqueTrading="0.1";						//Cantidad de bloques de divisas a negociar por cada interaccion.  Es un multiplicador con $SaldoMinimoTrading.  
															//Ej:  Para realizar una operacion de venta de un bloque (1) BCN se requieren minimo (100) BCN.  Es el bloque minimo a negociar
															//	   Si se va entonces a negociar bloques de 2 entonces se debe subir a 200 el $SaldoMinimoTrading
		$SaldoMinimoTrading=$TamanoBloqueTrading*1;			//Cantidad minima que se debe tener de la divisa a negociar para poder solicitar una venta de esta.  ESTA FORMULA PODRIA CAMBIAR POR CADA DIVISA!!!
	}

if ($MonedaValida==0)
	{
		echo "Moneda no seleccionada";
		die();
	}




//######################################################################
//######################################################################
function Separador($Caracter='-')
	{
		global $AnchoConsola;
		echo "\n\r";
		for($i=1;$i<=$AnchoConsola;$i++)
			echo $Caracter;
	}


//######################################################################
//######################################################################
function ColorTextoConsola($Foreground="",$Background="")
	{
		/*
			TABLA GUIA DE COLORES BASH
			Color	Foreground	Background
			black	30			40
			red		31			41
			green	32			42
			yellow	33			43
			blue	34			44
			magenta	35			45
			cyan	36			46
			white	37			47
		*/
		//Si no se define color usa el estandar de la consola o define predeterminados
		if ($Foreground=="" && $Background=="")
			$ColorFinal="\033[0m";
		else
			{
				if ($Foreground=="black") $Foreground="30";
				if ($Foreground=="red") $Foreground="31";
				if ($Foreground=="green") $Foreground="32";
				if ($Foreground=="yellow") $Foreground="33";
				if ($Foreground=="blue") $Foreground="34";
				if ($Foreground=="magenta") $Foreground="35";
				if ($Foreground=="cyan") $Foreground="36";
				if ($Foreground=="white") $Foreground="37";

				if ($Background=="black") $Background=";40";
				if ($Background=="red") $Background=";41";
				if ($Background=="green") $Background=";42";
				if ($Background=="yellow") $Background=";43";
				if ($Background=="blue") $Background=";44";
				if ($Background=="magenta") $Background=";45";
				if ($Background=="cyan") $Background=";46";
				if ($Background=="white") $Background=";47";
				$ColorFinal="\033[{$Foreground}{$Background}m";
			}
		echo "$ColorFinal";
	}


//######################################################################
//######################################################################
//Pone una orden compra o venta en el mercado Ej:EstablecerOrden("COMPRA","BCNBTC","1","0.0000010102");
function EstablecerOrden($TipoOperacion,$CriptoMoneda,$Cantidad,$Valor)
	{
		global $HitBtcAPI,$HitBtcSECRET;
		$client = new \Hitbtc\ProtectedClient($HitBtcAPI, $HitBtcSECRET, $demo = false);
		$newOrder = new \Hitbtc\Model\NewOrder();
		if ($TipoOperacion=="VENTA")
			$newOrder->setSide($newOrder::SIDE_SELL);
		if ($TipoOperacion=="COMPRA")
			$newOrder->setSide($newOrder::SIDE_BUY);
		$newOrder->setSymbol($CriptoMoneda);
		$newOrder->setTimeInForce($newOrder::TIME_IN_FORCE_GTC);
		$newOrder->setType($newOrder::TYPE_LIMIT);
		$newOrder->setQuantity($Cantidad);
		$newOrder->setPrice($Valor);

		try {
			$order = $client->newOrder($newOrder);
			return $order->getOrderId();
			//var_dump($order->getStatus()); // new
		} catch (\Hitbtc\Exception\RejectException $e) {
			echo $e; // if creating order will rejected
		} catch (\Hitbtc\Exception\InvalidRequestException $e) {
			echo $e->getMessage(); // error in request
		} catch (\Exception $e) {
			echo $e->getMessage(); // other error like network issue
		}
	}


//######################################################################
//######################################################################
function ObtenerSaldoTrading($CriptoMoneda,$TipoSaldo="DISPONIBLE")
	{
		global $HitBtcAPI,$HitBtcSECRET;
		$client = new \Hitbtc\ProtectedClient($HitBtcAPI, $HitBtcSECRET, $demo = false);
		
		try {
			foreach ($client->getBalanceTrading() as $balance) {
				// Verifica si el $balance->getCurrency() que se esta recorriendo es la moneda deseada para obtener el saldo
				if ($balance->getCurrency() == $CriptoMoneda)
					{
						if ($TipoSaldo=="DISPONIBLE")
							return $balance->getAvailable();
						if ($TipoSaldo=="RESERVADO")
							return $balance->getReserved();						
					}
			}
		} catch (\Hitbtc\Exception\InvalidRequestException $e) {
			echo $e;
		} catch (\Exception $e) {
			echo $e;
		}
	}


//######################################################################
//######################################################################
function ObtenerSaldoMain($CriptoMoneda)
	{
		global $HitBtcAPI,$HitBtcSECRET;
		$client = new \Hitbtc\ProtectedClient($HitBtcAPI, $HitBtcSECRET, $demo = false);
		try {
			foreach ($client->getBalanceMain() as $balance) {
				if ($balance->getCurrency() == $CriptoMoneda)
					return $balance->getAmount();
			}
		} catch (\Hitbtc\Exception\InvalidRequestException $e) {
			echo $e;
		} catch (\Exception $e) {
			echo $e;
		}
	}


//######################################################################
//######################################################################
function TransferirSaldo_TradingAMain($CriptoMoneda,$Cantidad)
	{
		global $HitBtcAPI,$HitBtcSECRET;
		$client = new \Hitbtc\ProtectedClient($HitBtcAPI, $HitBtcSECRET, $demo = false);
		try {
			$tnxId = $client->transferToMain($CriptoMoneda, $Cantidad);
		} catch (\Hitbtc\Exception\InvalidRequestException $e) {
			echo $e;
		} catch (\Exception $e) {
			echo $e;
		}
	}


//######################################################################
//######################################################################
function TransferirSaldo_MainATrading($CriptoMoneda,$Cantidad)
	{
		global $HitBtcAPI,$HitBtcSECRET;
		$client = new \Hitbtc\ProtectedClient($HitBtcAPI, $HitBtcSECRET, $demo = false);
		try {
			$tnxId = $client->transferToTrading($CriptoMoneda, $Cantidad);
		} catch (\Hitbtc\Exception\InvalidRequestException $e) {
			echo $e;
		} catch (\Exception $e) {
			echo $e;
		}
	}


//######################################################################
//######################################################################
function VerOrdenesActivas()
	{
		global $HitBtcAPI,$HitBtcSECRET;
		$client = new \Hitbtc\ProtectedClient($HitBtcAPI, $HitBtcSECRET, $demo = false);
		print_r($client->getActiveOrders());
	}


//######################################################################
//######################################################################
function VerOrdenesRecientes()
	{
		global $HitBtcAPI,$HitBtcSECRET;
		$client = new \Hitbtc\ProtectedClient($HitBtcAPI, $HitBtcSECRET, $demo = false);
		print_r($client->getRecentOrders());
	}


//######################################################################
//######################################################################
function VerTransacciones()
	{
		global $HitBtcAPI,$HitBtcSECRET;
		$client = new \Hitbtc\ProtectedClient($HitBtcAPI, $HitBtcSECRET, $demo = false);
		print_r($client->getTransactions());
	}


//######################################################################
//######################################################################
function VerTrading($DivisaComparadoraMercado)
	{
		global $HitBtcAPI,$HitBtcSECRET;
		$client = new \Hitbtc\ProtectedClient($HitBtcAPI, $HitBtcSECRET, $demo = false);
		print_r($client->getTrades("$DivisaComparadoraMercado"));
	}


//######################################################################
//######################################################################
function ObtenerLimiteVenta($CriptoMoneda)
	{
		global $HitBtcAPI,$HitBtcSECRET;
		$client = new \Hitbtc\ProtectedClient($HitBtcAPI, $HitBtcSECRET, $demo = false);
        $response = $client->getHttpClient()->get('/api/1/public/'.$CriptoMoneda.'/ticker', array('exceptions' => false));
        $document = $response->json();
		return $document["ask"];		
	}


//######################################################################
//######################################################################
function ObtenerLimiteCompra($CriptoMoneda)
	{
		global $HitBtcAPI,$HitBtcSECRET;
		$client = new \Hitbtc\ProtectedClient($HitBtcAPI, $HitBtcSECRET, $demo = false);
        $response = $client->getHttpClient()->get('/api/1/public/'.$CriptoMoneda.'/ticker', array('exceptions' => false));
        $document = $response->json();
		//print_r($client->getLimiteVenta($CriptoMoneda));
		return $document["bid"];
	}


//######################################################################
//######################################################################
function OperarDivisa($MonedaOperar,$DivisaComparadoraMercado,$DivisaDeSoporte,$SensibilidadMercado,$CambioOfertaMercado,$ComisionOperador,$TamanoBloqueTrading,$SaldoMinimoTrading,$SaldoMinimoSoporte,$DecimalesPrecision,$SaldoResidualSoporte)
	{	
		global $IntervaloEjecucion;
		$UltimoValorIdealCompra=0;
		$UltimoValorIdealVenta=0;
		$GananciaAcumulada=0;
		while(1==1)
			{
				$Momento=date("Y-m-d H:i:s");
				echo "\n\r\n\r";
				Separador("=");
				echo "\n\r                   ESTADO DEL MERCADO A: ".$Momento;
				Separador("=");
				
				$ValorBuyingBID=ObtenerLimiteCompra("$DivisaComparadoraMercado");  //Obtiene el Buying Bid para la moneda
				$ValorSellingASK=ObtenerLimiteVenta("$DivisaComparadoraMercado");  //Obtiene el Selling Ask para la moneda
				$DiferenciaVentaCompra=$ValorSellingASK-$ValorBuyingBID;
				
				if ($UltimoValorIdealCompra!=$ValorBuyingBID)
					{
						$ValorIdealCompra=$ValorBuyingBID + $CambioOfertaMercado;
						$UltimoValorIdealCompra=$ValorIdealCompra;
					}
				
				if ($UltimoValorIdealVenta!=$ValorSellingASK)
					{
						$ValorIdealVenta=$ValorSellingASK - $CambioOfertaMercado;
						$UltimoValorIdealVenta=$ValorIdealVenta;
					}
				
				echo "\n\r  Buying BID: $ValorBuyingBID    Selling ASK: $ValorSellingASK";
				echo "\n\r  Diferencia: ".number_format($DiferenciaVentaCompra,$DecimalesPrecision);
				
				if ($DiferenciaVentaCompra > $SensibilidadMercado)
					{
						$SaldoDisponible=ObtenerSaldoTrading("$MonedaOperar","DISPONIBLE");
						$SaldoDisponibleSoporte=ObtenerSaldoTrading("$DivisaDeSoporte","DISPONIBLE");
						Separador("-");
						ColorTextoConsola("black","yellow");
						echo "\n\r  Saldo actual   $MonedaOperar:   ".$SaldoDisponible."     $DivisaDeSoporte:       ".number_format($SaldoDisponibleSoporte,$DecimalesPrecision);
						ColorTextoConsola();
						//Opera solamente cuando hay saldos disponibles para comprar y para vender asi garantizo que compro a un precio bajo pero lo vendo a uno mas alto al mismo tiempo (o cuando la orden de venta sea llenada)
						if ($SaldoDisponible > $SaldoMinimoTrading && $SaldoDisponibleSoporte > $SaldoMinimoSoporte)
							{
								$IdOrdenCompra=EstablecerOrden("COMPRA","$DivisaComparadoraMercado","$TamanoBloqueTrading",number_format($ValorIdealCompra,$DecimalesPrecision));
								ColorTextoConsola("black","green");
								echo "\n\r  Creando orden  COMPRA: #$IdOrdenCompra     VALOR      ".number_format($ValorIdealCompra,$DecimalesPrecision);
								$IdOrdenVenta=EstablecerOrden("VENTA","$DivisaComparadoraMercado","$TamanoBloqueTrading", number_format($ValorIdealVenta, $DecimalesPrecision));
								ColorTextoConsola("black","red");
								echo "\n\r  Creando orden  VENTA:  #$IdOrdenVenta     VALOR      ".number_format($ValorIdealVenta,$DecimalesPrecision);
								$GananciaOperacion=$ValorIdealVenta-$ValorIdealCompra;
								$GananciaAcumulada+=$GananciaOperacion;
								ColorTextoConsola("white","blue");
								echo "\n\r  Ganancia       ORDEN: ".number_format(($GananciaOperacion*$SaldoMinimoTrading),$DecimalesPrecision)."     ACUMULADA: ".number_format(($GananciaAcumulada*$SaldoMinimoTrading),$DecimalesPrecision)." - {$ComisionOperador}%";
								ColorTextoConsola();

								//Despues de establecer ordenes mira si hay saldos residuales en la divisa de soporte y los transfiere a la cuenta para reservarlos
								//$SaldoDisponibleSoporte=ObtenerSaldoTrading("$DivisaDeSoporte","DISPONIBLE");
								//if ($SaldoDisponibleSoporte>$SaldoMinimoSoporte*3)
								//	TransferirSaldo_TradingAMain("BTC",$SaldoResidualSoporte);
							}
						else
							{
								ColorTextoConsola("blue");
								echo "\n\r  --> Sin saldos minimos para operar el mercado con seguridad <--";
								echo "\n\r  Ganancia                               ACUMULADA: ".number_format(($GananciaAcumulada*$SaldoMinimoTrading),$DecimalesPrecision)." - {$ComisionOperador}%";
								ColorTextoConsola();
							}
					}
				else
					{
						ColorTextoConsola("blue");
						echo "\n\r  --> IGNORANDO cualquier operacion del mercado";
						echo "\n\r  Ganancia                               ACUMULADA: ".number_format(($GananciaAcumulada*$SaldoMinimoTrading),$DecimalesPrecision)." - {$ComisionOperador}%";
						ColorTextoConsola();
					}
				sleep($IntervaloEjecucion);				
			}
	}

//Llama al hilo principal del bot
OperarDivisa($MonedaOperar,$DivisaComparadoraMercado,$DivisaDeSoporte,$SensibilidadMercado,$CambioOfertaMercado,$ComisionOperador,$TamanoBloqueTrading,$SaldoMinimoTrading,$SaldoMinimoSoporte,$DecimalesPrecision,$SaldoResidualSoporte);



//echo ObtenerSaldoTrading("BCN","RESERVADO");	//DISPONIBLE|RESERVADO
//TransferirSaldo_MainATrading("BCN",100);
//TransferirSaldo_TradingAMain("BCN",100);

//VerOrdenesRecientes();
//VerOrdenesActivas();
//ObtenerSaldoMain("XDN");
//VerTransacciones();
//VerTrading("BCNBTC");









/*
Informacion general de la API:
* https://hitbtc.com/api#orderbook
* https://github.com/hitbtc-com/hitbtc-api#errors


Symbol	Lot size	Price step
BTCUSD	0.01 BTC	0.01
BTCEUR	0.01 BTC	0.01
LTCBTC	0.1 LTC	0.00001
LTCUSD	0.1 LTC	0.001
LTCEUR	0.1 LTC	0.001
DSHBTC	1 DSH	0.00000001
ETHBTC	0.001 ETH	0.000001
ETHEUR	0.001 ETH	0.0001
NXTBTC	1 NXT	0.00000001
BCNBTC	100 BCN	0.0000000001
XDNBTC	100 XDN	0.0000000001
DOGEBTC	1000 DOGE	0.000000001
XMRBTC	0.01 XMR	0.000001
QCNBTC	0.01 QCN	0.000001
FCNBTC	0.01 FCN	0.000001
LSKBTC	1 LSK	0.0000001
LSKEUR	1 LSK	0.0001
STEEMBTC	0.001 STEEM	0.00001
STEEMEUR	0.001 STEEM	0.0001
SBDBTC	0.001 SBD	0.00001
DASHBTC	0.001 DASH	0.000001
XEMBTC	1 XEM	0.00000001
EMCBTC	0.1 EMC	0.00000001
SCBTC	100 SC	0.000000001
ARDRBTC	1 ARDR	0.000000001
ZECBTC	0.001 ZEC	0.000001
WAVESBTC	0.01 WAVES	0.0000001
*/
