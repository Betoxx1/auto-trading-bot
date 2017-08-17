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


//######################################################################
//######################################################################
/*
	Function: OperarDivisa_InferenciaSimple
			  Permite operar a partir del precio actual del mercado,
			  revisando los precios actuales de venta y compra, cambia
			  las ofertas a una unidad minima y maxima por encima y por
			  debajo de los precios actuales para permanecer en la parte
			  superior de las ordenes.

	Variables minimas de entrada:
		Lista de parametros completos para realizar la operacion

	Salida de la funcion:
		* Ejecucion infinita cada cierto numero de segundos para buscar en cada momento la posibilidad de venta y compra con los margenes de ganancia definidos.
*/
function OperarDivisa_InferenciaSimple($MonedaOperar,$DivisaComparadoraMercado,$DivisaDeSoporte,$SensibilidadMercado,$CambioOfertaMercado,$ComisionOperador,$TamanoBloqueTrading,$SaldoMinimoTrading,$SaldoMinimoSoporte,$DecimalesPrecision,$SaldoResidualSoporte)
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


//######################################################################
//######################################################################
/*
	Function: OperarDivisa_InferenciaPorTendencia
			  Permite operar a partir de la tendencia del mercado
			  contando en las ultimas 5 operaciones cual es la tendencia
			  para saber si esta a la alza o a la baja

	Variables minimas de entrada:
		Lista de parametros completos para realizar la operacion

	Salida de la funcion:
		* Ejecucion infinita cada cierto numero de segundos para buscar en cada momento la posibilidad de venta y compra con los margenes de ganancia definidos.
*/
function OperarDivisa_InferenciaPorTendencia($MonedaOperar,$DivisaComparadoraMercado,$DivisaDeSoporte,$SensibilidadMercado,$CambioOfertaMercado,$ComisionOperador,$TamanoBloqueTrading,$SaldoMinimoTrading,$SaldoMinimoSoporte,$DecimalesPrecision,$SaldoResidualSoporte)
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
