<?

require("./class/diagram.php");

$oDiagramm = new CDiagram(500,500,"PNG");

$oDiagramm->setDiagramType("Line");
$oDiagramm->setBackground(255,255,255);
$oDiagramm->setTextcolor(0,0,0);
$oDiagramm->setLinecolor(0,0,0);
$oDiagramm->setGraphcolor(0,0,255);
$oDiagramm->setValuecolor(255,0,0);
$i=0;
$r=0;
while ($i<3600)
{
$oDiagramm->setData($r*cos($i),$r*sin($i));
$i=$i+25;
$r=$r+0.06;
}

$oDiagramm->setX(10,-10,1);
$oDiagramm->setY(10,-10,1);

$oDiagramm->paintHeader("Schnecke",4);
$oDiagramm->paintXY();
$oDiagramm->paintScale("X", "Y",3);
$oDiagramm->paintData();

$oDiagramm->toFile("./test1.png");
echo '<img src="./test1.png">'

?>
