<?
require("./class/diagram.php");

$oDiagramm = new CDiagram(500,200,"PNG");

$oDiagramm->setDiagramType("Balken");
$oDiagramm->setBackground(255,255,255);
$oDiagramm->setTextcolor(0,0,0);
$oDiagramm->setLinecolor(0,0,0);
$oDiagramm->setGraphcolor(0,0,255);
$oDiagramm->setValuecolor(255,0,0);
$oDiagramm->setData(1,41);
$oDiagramm->setData(2,42);
$oDiagramm->setData(3,20);
$oDiagramm->setData(4,40);
$oDiagramm->setData(5,39);
$oDiagramm->setData(6,38);
$oDiagramm->setData(7,-15);
$oDiagramm->setData(8,32);
$oDiagramm->setData(9,33);
$oDiagramm->setData(10,34);
$oDiagramm->setData(11,35);
$oDiagramm->setData(12,33);
$oDiagramm->setData(13,41.1);
$oDiagramm->setData(14,41.1);
$oDiagramm->setData(15,41.1);
$oDiagramm->setData(16,34);
$oDiagramm->setData(17,41.1);
$oDiagramm->setData(18,41.1);
$oDiagramm->setData(19,41.1);
$oDiagramm->setData(20,41);
$oDiagramm->setData(21,41.1);
$oDiagramm->setData(22,41.1);
$oDiagramm->setData(23,41.1);
$oDiagramm->setData(24,35);

$oDiagramm->setX(28,0,1);
$oDiagramm->setY(42,-42,10);
$oDiagramm->enable("ItemData");
$oDiagramm->setItemData(1,"Mo");
$oDiagramm->setItemData(4,"Di");
$oDiagramm->setItemData(8,"Mi");
$oDiagramm->setItemData(12,"Do");
$oDiagramm->setItemData(16,"Fr");
$oDiagramm->setItemData(20,"Sa");
$oDiagramm->setItemData(24,"So");

$oDiagramm->paintHeader("Modem Receive Power",4);
$oDiagramm->paintXY();
$oDiagramm->paintScale("Time", "Receive Power",3);
$oDiagramm->paintData();

$oDiagramm->show();
?>
