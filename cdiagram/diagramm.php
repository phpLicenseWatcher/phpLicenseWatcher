<?

require("./class/diagram.php");
require("./class/CDiagramm.php");

$oDiagramm = new CDiagramm(600,300,"PNG");

$oDiagramm->setBackground(255,255,255);
$oDiagramm->setTextcolor(0,0,0);
$oDiagramm->setLinecolor(0,0,0);
$oDiagramm->setGraphcolor(255,0,0);
$oDiagramm->setValuecolor(255,0,0);
$oDiagramm->setData(1,2);
$oDiagramm->setData(2,1.4);
$oDiagramm->setData(3,1.3);
$oDiagramm->setData(4,2.2);
$oDiagramm->setData(5,-3);
$oDiagramm->setData(6,1);

$oDiagramm->setValue(3.4,2);

$oDiagramm->setX(7,-1,1);
$oDiagramm->setY(2,-2,0.5);
$oDiagramm->enable("ItemData");
$oDiagramm->setItemData(2,"Hello1");
$oDiagramm->setItemData(4,"Hello2");
$oDiagramm->paintHeader("Modem Receive Power",4);
$oDiagramm->paintXY();
$oDiagramm->paintScale("Time", "Receive Power",3);
$oDiagramm->paintData();
$oDiagramm->paintValue();

#$oDiagramm->toFile("test1.png");
$oDiagramm->show();
$oDiagramm->destroy();
?>
