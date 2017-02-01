<?

require("./class/diagram.php");

$oDiagramm = new CDiagram(600,300,"PNG");

$oDiagramm->setBackground(255,255,255);
$oDiagramm->setTextcolor(0,0,0);
$oDiagramm->setLinecolor(0,0,0);
$oDiagramm->setGraphcolor(0,0,255);
$oDiagramm->setValuecolor(255,0,0);



$oDiagramm->setX(5,0,0.5);
$oDiagramm->setY(4,-4,1);

$oDiagramm->setDiagramType("Line");
	$oDiagramm->setData(1,4);
	$oDiagramm->setData(2,3);
	$oDiagramm->setData(3,2);
	$oDiagramm->setData(4,-3);
	$oDiagramm->paintData();
$oDiagramm->clearData();

$oDiagramm->setDiagramType("Balken");
	$oDiagramm->setData(1,4);
	$oDiagramm->setData(4,-3);
	$oDiagramm->paintData();
$oDiagramm->clearData();

$oDiagramm->setDiagramType("Line");
	$oDiagramm->setData(1.5,4);
	$oDiagramm->setData(2.5,3);
	$oDiagramm->setData(3.5,2);
	$oDiagramm->setData(4.5,-3);
	$oDiagramm->paintData();
$oDiagramm->clearData();

	$oDiagramm->setValue(3.0,2);
	$oDiagramm->setValue(3.5,2);
$oDiagramm->paintValue();

$oDiagramm->paintHeader("Modem Receive Power",4);
$oDiagramm->paintXY();
$oDiagramm->paintScale("Time", "Receive Power",3);

#$oDiagramm->toFile("test1.png");
$oDiagramm->show();
#echo '<img src="./test1.png">'

?>
