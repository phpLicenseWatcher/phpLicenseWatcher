<?

require("./class/diagram.php");
require("./class/CDiagramm.php");

$oDiagramm = new CDiagramm(500,300,"PNG");

$oDiagramm->setDiagramType("Lagrange");
$oDiagramm->setBackground(255,255,255);
$oDiagramm->setTextcolor(0,0,0);
$oDiagramm->setLinecolor(0,0,0);
$oDiagramm->setGraphcolor(0,0,255);
$oDiagramm->setValuecolor(255,0,0);
$oDiagramm->setData(-1,0);
$oDiagramm->setData(0,-1);
$oDiagramm->setData(1,0);
$oDiagramm->setData(4,0);
$oDiagramm->setData(5,-1.5);
$oDiagramm->setData(6,1);
$oDiagramm->setData(7,2);

$oDiagramm->setX(7,-1,1);
$oDiagramm->setY(4,-2,0.5);
$oDiagramm->paintHeader("Lagrange-Polynome",4);
$oDiagramm->paintXY();
$oDiagramm->paintScale("X", "Y",3);
$oDiagramm->paintData();
$oDiagramm->paintValue();

$oDiagramm->toFile("test1.png");
$oDiagramm->show();
$oDiagramm->destroy();
?>
