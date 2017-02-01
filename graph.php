<?php

define("RRDTOOL", "/usr/bin/rrdtool");

$rrd_dir="/var/www/html/phplicensewatcher/rrd";
$metricname = "msimlevsimvlog";
$vlabel = "Licenses";
$subtitle = "Modelsim";
$size = "small";

$style = "";

$subtitle = $metricname;

if (is_numeric($max))
         $upper_limit = "--upper-limit '$max' ";
if (is_numeric($min))
         $lower_limit ="--lower-limit '$min' ";

if ($vlabel)
         $vertical_label = "--vertical-label '$vlabel'";
else if ($upper_limit or $lower_limit)
         {
            $max = $max>1000 ? number_format($max) : number_format($max, 2);
            $min = $min>0 ? number_format($min,2) : $min;

            $vertical_label ="--vertical-label '$min - $max' ";
}

$rrd_file = "$rrd_dir/$metricname.rrd";
$default_metric_color = "5555cc";
$series = "DEF:'sum'='$rrd_file':'sum':AVERAGE "
         ."AREA:'sum'#$default_metric_color:'$subtitle' ";
if ($jobstart)
         $series .= "VRULE:$jobstart#$jobstart_color ";

# Set the graph title.
if($context == "meta")
   {
     $title = "$self $meta_designator $style last $range";
   }
else if ($context == "grid")
  {
     $title = "$grid $meta_designator $style last $range";
  }
else if ($context == "cluster")
   {
      $title = "$clustername $style last $range";
   }
else
   {
    if ($size == "small")
      {
        # Value for this graph define a background color.
        if (!$load_color) $load_color = "ffffff";
        $background = "--color BACK#'$load_color'";

        $title = $hostname;
      }
    else if ($style)
       $title = "$hostname $style last $range";
    else
       $title = $metricname;
   }

# Calculate time range.
if ($sourcetime)
   {
      $end = $sourcetime;
      # Get_context makes start negative.
      $start = $sourcetime + $start;
   }
# Fix from Phil Radden, but step is not always 15 anymore.
if ($range=="month")
   $end = floor($end / 672) * 672;

#
# Generate the rrdtool graph command.
#

$end = time();
$start = $end - 3600;
$width = 400;
$height = 200;
$fudge += $height;

#$command = RRDTOOL . " graph - --start $start --end $end ".
#   "--width $width --height $fudge $upper_limit $lower_limit ".
#   "--title '$title' $vertical_label $extras $background ".
#   $series;
$command = RRDTOOL . " graph - --start='end-3 day' --end=now --imgformat=PNG --width=500 --base=1000 --height=120 --interlaced DEF:a=/var/www/html/phplicensewatcher/rrd//msimlevsimvlog.rrd:msimlevsimvlog:MAX AREA:a#800000:ModelSim";

$debug=0;

# Did we generate a command?   Run it.
if($command)
 {
   /*Make sure the image is not cached*/
   header ("Expires: Mon, 26 Jul 1997 05:00:00 GMT");   // Date in the past
   header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
   header ("Cache-Control: no-cache, must-revalidate");   // HTTP/1.1
   header ("Pragma: no-cache");                     // HTTP/1.0
   if ($debug) {
     header ("Content-type: text/html");
     print "$command\n\n\n\n\n";
    }
   else {
     header ("Content-type: image/png");
     passthru($command);
    }
 }

?>
