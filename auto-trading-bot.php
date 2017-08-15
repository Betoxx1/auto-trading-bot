#!/usr/bin/php
<?php
/*				  
	AUTO-TRADING-BOT						Copyright (C) 2017
	----------------						John F. Arroyave Gutiérrez
	                					  	unix4you2@gmail.com
	                					  	www.practico.org

    This program is free software; you can redistribute it and/or
    modify it under the terms of the GNU General Public License
    as published by the Free Software Foundation; either version 2
    of the License, or (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,MA 02110-1301,USA.
*/

require ("include/config.php");						//Configuracion personal de llaves de API
require ("include/z_consola.php");					//Funciones para impresion a consolas basicas (CLI)
include ("vendor-".$API_Trader."/autoload.php");	//Dependencias de la API seleccionada para operar
require ("include/z_api-".$API_Trader.".php");		//Funciones propias para operar el mercado con la API especificada
require ("include/z_divisas.php");					//Configuraciones basicas de divisa o moneda


$IntervaloEjecucion=10;								//En segundos, tiempo de descanso del Bot antes de buscar nuevamente los cambios del mercado


//######################################################################
//######################################################################
function OperarDivisa($MonedaOperar,$DivisaComparadoraMercado,$DivisaDeSoporte,$SensibilidadMercado,$CambioOfertaMercado,$ComisionOperador,$TamanoBloqueTrading,$SaldoMinimoTrading,$SaldoMinimoSoporte,$DecimalesPrecision,$SaldoResidualSoporte)
	{	
		global $IntervaloEjecucion,$AnchoConsola;
		$UltimoValorIdealCompra=0;
		$UltimoValorIdealVenta=0;
		$GananciaAcumulada=0;
		while(1==1)
			{
				$Momento=date("Y-m-d H:i:s");
				echo "\n\r\n\r";
				Separador("=",$AnchoConsola);
				echo "\n\r                   ESTADO DEL MERCADO A: ".$Momento;
				Separador("=",$AnchoConsola);
				
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
						Separador("-",$AnchoConsola);
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
