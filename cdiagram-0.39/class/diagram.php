<?php
class CDiagram
{

var $m_width;		# Image-Width
var $m_height;		# Image-Height
var $m_bgcolor=0;	# Backgroundcolor
var $m_txcolor=0;	# Textcolor
var $m_licolor=0;	# Linecolor
var $m_grcolor=0;	# Graphcolor
var $m_vlcolor=0;	# Valuecolor

var $m_img;			# Image-Handler
var $m_img_type = "notSet";	# Image-Type

var $m_xmax=0;		# X-Max
var $m_xmin=0;		# X-Min
var $m_xstep=0;		# X-Step

var $m_ymax=0;		# Y-Max
var $m_ymin=0;		# Y-Min
var $m_ystep=0;		# Y-Step

var $m_xwidth;		# X-Width
var $m_yheight;		# Y-Height

var $m_data=array();		# Data-Points one graph
var $m_value=array();		# Value-Points

var $m_cpdata=array();		# Copy der Data-Points falls n�ig (muss erzeugt werden)
var $m_cutData=0;	# CutData-Enabler
var $m_ItemData=0; 	# Itemdata-Enabler
var $m_ZeroLine=1;	# ZeroLine-Enabler

var $m_ItemDataArray=array();	# ItemData-Points

var $m_DiagramType="Line";	# Diagramtype (Line, Balken)
var $m_SQLConnection;		# SQL-Verbindung

function CDiagram($width,$height,$type)
{

	$this->m_width	=$width;
	$this->m_height	=$height;
	
	$this->m_xwidth=$width-70;
	$this->m_yheight=$height-100;
	
	$this->setImageType($type);
	$this->m_img	=imagecreate($width,$height);

	$this->m_DiagramType="Line";
}

function destroy()
{
	imagedestroy($this->m_img);
}

# Output-Methods 

function show()
{
	switch($this->m_img_type)
	{
		case "PNG":
			header("Content-type: image/png");
			imagepng($this->m_img);
		break;
		case "GIF":
			header("Content-type: image/gif");
			imagegif($this->m_img);
		break;
		case "notSet":
			$this->noType();
		break;
	}
}

function toFile($filename)
{
	switch($this->m_img_type)
	{
		case "PNG":
			imagepng($this->m_img,$filename);
			imagedestroy($this->m_img);
		break;
		case "GIF":
			header("Content-type: image/gif");
			imagegif($this->m_img, $filename);
		break;
		case "notSet":
			$this->noType();
		break;
	}
}

# Set-Methods

function setImageType($type)
{
	$this->m_img_type=$type;
}


function setBackground($r,$g,$b)
{
	$this->m_bgcolor = ImageColorAllocate ($this->m_img, $r,$g,$b);
}

function setTextcolor($r,$g,$b)
{
	$this->m_txcolor = ImageColorAllocate ($this->m_img, $r,$g,$b);
}

function setLinecolor($r,$g,$b)
{
	$this->m_licolor = ImageColorAllocate ($this->m_img, $r,$g,$b);
}

function setGraphcolor($r,$g,$b)
{
	$this->m_grcolor = ImageColorAllocate ($this->m_img, $r,$g,$b);
}

function setValuecolor($r,$g,$b)
{
	$this->m_vlcolor = ImageColorAllocate ($this->m_img, $r,$g,$b);
}

function setData($x,$y)
{
	$this->m_data[]=array($x,$y);
}

	
function setSQL($SQLType,$SQLServer,$SQLDatabase,$UserName,$Password,$XField,$YField)
{
# SQL-Type: 1 - MySQL

	$this->m_SQLConnection=new sql($SQLType,$SQLServer,$SQLDatabase,$XField,$YField);
	$this->m_SQLConnection->auth($UserName,$Password);
	
}

function setSQLWHERE($WHERE)
{
	$this->m_SQLConnection->where($WHERE);
}

function setSQLORDER($ORDER)
{
	$this->m_SQLConnection->order($ORDER);
}

function setDatabySql()
{
	$this->m_data=$this->m_SQLConnection->getDataField();
}

function setItemData($index,$Value)
{
	$this->m_ItemDataArray=$this->m_ItemDataArray + array($index=>"$Value");
}

function setValue($x,$y)
{
	$this->m_value[]=array($x,$y);
}

function setX($xmax,$xmin,$xstep)
{
	$this->m_xmax=$xmax;
	$this->m_xmin=$xmin;
	$this->m_xstep=$xstep;
}

function setY($ymax,$ymin,$ystep)
{
	$this->m_ymax=$ymax;
	$this->m_ymin=$ymin;
	$this->m_ystep=$ystep;
}

function setDiagramType($Type)
{
	$this->m_DiagramType=$Type;
}

# Clear-Methods

function clearData()
{
	$this->m_data=array();
}

function clearValue()
{
	$this->m_value=array();
}
# Paint-Methods

function paintHeader($text,$font)
{
	imagestring ($this->m_img, $font, 10,10,$text,$this->m_txcolor);
}	 

function paintXY()
{

       	# Rahmen
	imagerectangle ($this->m_img,0,0,$this->m_width-1,$this->m_height-1,$this->m_licolor);
	# XY-System
	imageline ($this->m_img,50,$this->m_height-50,$this->m_width-20,$this->m_height-50,$this->m_licolor);
	imageline ($this->m_img,50,$this->m_height-50,50,50,$this->m_licolor);

	if ($this->m_ZeroLine==1)
	{
		# X-Nul
		if ($this->m_xmin<=0)
		{
			for ($i=$this->m_height-70;$i>=50;$i=$i-20)
			{
				imageline ($this->m_img,$this->calcX(0),$i,$this->calcX(0),$i+10,$this->m_licolor);
			}
		}
		# Y-Nul
		if ($this->m_ymin<=0)
		{
			for ($i=50;$i<=$this->m_width-20;$i=$i+20)
			{
				imageline ($this->m_img,$i,$this->calcY(0),$i+10,$this->calcY(0),$this->m_licolor);
			}
		}
	}
}

function paintScale($textX,$textY,$font)
{
	imagestringup ($this->m_img, $font,5,$this->m_height-50,$textY,$this->m_txcolor);
	imagestring ($this->m_img, $font,50,$this->m_height-20,$textX,$this->m_txcolor);

	$xdot=$this->calcXdot();
	$xstep=($this->m_xstep)*$xdot;
	$xstepcount=$this->m_xmin;

	for ($i=50;$i<=$this->m_width-20;$i=$i+$xstep)
	{
		imageline ($this->m_img,$i,$this->m_height-55,$i,$this->m_height-45,$this->m_licolor);
		if ($this->m_ItemData==0) {
			imagestring ($this->m_img,$font,$i-3,$this->m_height-40,$xstepcount,$this->m_txcolor);
		} else {
			if ( isset($this->m_ItemDataArray[$xstepcount]) )
			imagestring ($this->m_img,$font,$i-3,$this->m_height-40,$this->m_ItemDataArray[$xstepcount],$this->m_txcolor); 
			else 
			imagestring ($this->m_img,$font,$i-3,$this->m_height-40,"",$this->m_txcolor); 
		}
		$xstepcount=$xstepcount+$this->m_xstep;
	}

	$ydot=$this->calcYdot();
	$ystep=($this->m_ystep)*$ydot;
	$ystepcount=$this->m_ymin;

	for ($i=$this->m_height-50;$i>=50;$i=$i-$ystep)
	{
		imageline ($this->m_img,45,$i,55,$i,$this->m_licolor);
		if ($ystepcount>=0) 
			imagestring ($this->m_img,$font,25,$i-5,$ystepcount,$this->m_txcolor);
		else
			imagestring ($this->m_img,$font,20,$i-5,$ystepcount,$this->m_txcolor);
			
		$ystepcount=$ystepcount+$this->m_ystep;
	}

	
}

function paintData()
{
switch($this->m_DiagramType)
{
	case "Line":
		$this->paintDataLine();
	break;
	case "Balken":
		$this->paintDataBalken();
	break;
	case "Lagrange":
		$this->paintLagrange();
	break;
}
}

function paintLagrange()
{

	$this->cpdata();
	$this->clearData();

	$x=$this->m_xmin;
	while ($x<$this->m_xmax)
	{
	  	$y=$this->calcLagrange($x);
		$x=$x+0.1;
	$this->setData($x,$y);
	}

	$this->paintDataLine();
}

function paintDataBalken()
{
	foreach ($this->m_data as $dot)
	{
		$x1=$this->calcX($dot[0]-($this->m_xstep/2));
		$x2=$this->calcX($dot[0]+($this->m_xstep/2));
		$y1=$this->calcY($dot[1]);
		if ($this->m_ymin<0)
		{
			$y0=$this->calcY(0);
		} else {
			$y0=$this->calcY($this->m_ymin);
		}

		if ($dot[1]>0)
		{
			imagerectangle($this->m_img,$x1,$y1,$x2,$y0,$this->m_licolor);
			imagefilledrectangle($this->m_img,$x1+1,$y1+1,$x2-1,$y0-1,$this->m_grcolor);
		} else {
			imagerectangle($this->m_img,$x1,$y0,$x2,$y1,$this->m_licolor);
			imagefilledrectangle($this->m_img,$x1+1,$y0+1,$x2-1,$y1-1,$this->m_grcolor);
		}
	}
}

function paintDataLine()
{


	$dot=$this->m_data[0];

	$xold=$dot[0];
	$yold=$dot[1];

	foreach ($this->m_data as $dot)
	{
	if ($this->m_cutData==0) 
		{
		imageline($this->m_img,$this->calcX($xold),$this->calcY($yold),$this->calcX($dot[0]),$this->calcY($dot[1]),$this->m_grcolor);
		$xold=$dot[0];
		$yold=$dot[1];
		} else {
			if ($dot[1]<$this->m_ymin)
			{
				imageline($this->m_img,$this->calcX($xold),$this->calcY($yold),$this->calcX($this->calcCutDataX($xold,$yold,$dot[0],$dot[1])),$this->calcY($this->m_ymin),$this->m_grcolor);
				$xold=$dot[0];
				$yold=$dot[1];
			} else if ($yold<$this->m_ymin)
			{
				imageline($this->m_img,$this->calcX($this->calcCutDataX($xold,$yold,$dot[0],$dot[1])),$this->calcY($this->m_ymin),$this->calcX($dot[0]),$this->calcY($dot[1]),$this->m_grcolor);
				$xold=$dot[0];
				$yold=$dot[1];
				
			} else 
			{
				imageline($this->m_img,$this->calcX($xold),$this->calcY($yold),$this->calcX($dot[0]),$this->calcY($dot[1]),$this->m_grcolor);
				$xold=$dot[0];
				$yold=$dot[1];
			}
		}
	}

}


function paintValue()
{
	foreach ($this->m_value as $dot)
	{

		$x=$this->calcX($dot[0]);
		$y=$this->calcY($dot[1]);
	
		imageline($this->m_img,$x,$y,$x,$this->m_height-50,$this->m_vlcolor);
	}
}

# Calc-Methods

function calcXdot()
{
if ($this->m_xmin<0)
	$xdot=($this->m_width-70) / ($this->m_xmax+($this->m_xmin*(-1)));
else
	$xdot=($this->m_width-70) / ($this->m_xmax-($this->m_xmin*(1)));
	
return $xdot;
}

function calcYdot()
{
if ($this->m_ymin<0)
	$ydot=($this->m_height-100) / ($this->m_ymax+($this->m_ymin*(-1)));
else
	$ydot=($this->m_height-100) / ($this->m_ymax-($this->m_ymin*(1)));

return $ydot;
}

function calcXdiff($x)
{
	if ($this->m_xmin<0)	
	{
		$xdiff=($this->m_xmin*(-1)+$x);
	} else {
		$xdiff=($x-$this->m_xmin);
	}

return $xdiff;
		
}

function calcYdiff($y)
{
	if ($this->m_ymin<0)	
	{
		$ydiff=($this->m_ymin*(-1)+$y);
	} else {
		$ydiff=($y-$this->m_ymin);
	}

return $ydiff;
}

function calcX($x)
{

$xdot=$this->calcXdot();
$xdiff=$this->calcXdiff($x);
$x_value=($xdot*$xdiff)+50;

return $x_value;
}

function calcY($y)
{

$ydot=$this->calcYdot();
$ydiff=$this->calcYdiff($y);
$y_value=($this->m_height-50)-($ydot*$ydiff);

return $y_value;
}

function calcCutDataX($x1,$y1,$x2,$y2)
{
echo $x2;
$m=($y1-$y2) / ($x1-$x2);
$n=$y1-($m*$x1);

return ($this->m_ymin-$n) / $m;

}

# Lagrange-Polynom berechnen
function calcLagrange($x)
{

	$xcount=0;
	$ycount=0;
	$i=0; $j=0;
	foreach ($this->m_cpdata as $dot)
	{
		$xdot[$xcount]=$dot[0];
		$ydot[$ycount]=$dot[1];
		$xcount++;
		$ycount++;
	}
	$xcount--;
	$ycount--;
	$y=0;
	for ($j=0;$j<=$xcount;$j++)
	{
	
		$yp=1;
		for ($i=0;$i<=$xcount;$i++)
		{
			if ($i!=$j)
			{
				$yp=$yp*(($x-$xdot[$i])/($xdot[$j]-$xdot[$i]));
			}
		}
		$yp=$ydot[$j]*$yp;
		$y=$y+$yp;	
	}	
	return $y;
}

# Feature-Enabler
function enable($key)
{
switch ($key)
	{
	case "CutData":
		$this->m_cutData=1;
		break;
	case "ItemData":
		$this->m_ItemData=1;
		break;
	case "ZeroLine":
		$this->m_ZeroLine=1;
		break;
	}
}


function disable($key)
{
switch ($key)
	{
	case "CutData":
		$this->m_cutData=0;
		break;
	case "ZeroLine":
		$this->m_ZeroLine=0;
		break;
	}

}

# Copy datafield
function cpdata()
{
	$this->m_cpdata=array();
	$this->m_cpdata=$this->m_data;
}

# Errorhandler
function noType()
{
 echo "please use setImageType(TYPE)<BR>
       Type:    GIF<BR>
       Type:    PNG<BR>";
}

}
?>
