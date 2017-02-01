#! /usr/bin/perl -T
#
# Copyright (C) 2002, 2003, 2004, 2005, 2006, 2007, 2008 Christophe Kalt
#
# Redistribution and use in source and binary forms, with or without
# modification, are permitted provided that the following conditions
# are met:
# 1. Redistributions of source code must retain the above copyright
#    notice, this list of conditions and the following disclaimer.
# 2. Redistributions in binary form must reproduce the above copyright
#    notice, this list of conditions and the following disclaimer in the
#    documentation and/or other materials provided with the distribution.
# 3. All advertising materials mentioning features or use of this software
#    must display the following acknowledgement:
#      This product includes software developed by Christophe Kalt.
# 4. The name of the author may not be used to endorse or promote products
#     derived from this software without specific prior written permission.
#
# THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR
# IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
# OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
# IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
# SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED
# TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
# PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
# LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
# NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
# EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
#
# $Id: drraw.cgi 1526 2008-10-06 17:01:40Z kalt $
#
# Home Page -- http://web.taranis.org/drraw/
#

use warnings;
use strict;
use CGI qw(:standard :html3 *table *ul -no_xhtml -nosticky);
use CGI::Carp qw(fatalsToBrowser);
use Config;
use Fcntl;
use File::Basename;
use File::Find;
use POSIX qw(strftime);

# The following line is needed if "RRDs.pm" was not installed in a
# directory mentioned in your perl's @INC.
#use lib '/usr/local/rrdtool/lib/perl';
use RRDs;

# The configuration file is expected to be found in the same directory
# as drraw itself.  You may customize this to be elsewhere.
my $config = (dirname($0) =~ /(.*)/)[0] . "/drraw.conf"; # Untaint

# This needs to be manually set for stupid stupid File::Find to work
# in tainted mode.
$ENV{'PATH'} = '/bin:/usr/bin';

###############################################################################
##   STOP STOP STOP STOP STOP STOP STOP STOP STOP STOP STOP STOP STOP STOP   ##
##                                                                           ##
##      There should be no need for you to change any of what is below!      ##
##                                                                           ##
##   STOP STOP STOP STOP STOP STOP STOP STOP STOP STOP STOP STOP STOP STOP   ##
###############################################################################

# Configuration variable declarations, and default settings
use vars qw ( $title $header $footer
              %datadirs $dsfilter_def @rranames %rranames $vrefresh $drefresh
              @dv_def @dv_name @dv_secs $gformat $maxtime $crefresh
              $use_rcs $saved_dir $clean_cache $tmp_dir $ERRLOG %users
              %Index $IndexMax $icon_new $icon_closed $icon_open $icon_text
              $icon_help $icon_bug $icon_link
              %colors
              $CSS $CSS2 $bgColor );

# Default title
$title = 'Draw Round Robin Archives on the Web';
$header = '';
$footer = '';

# *.rrd and *.evt file list sorting function
sub datafnsort {
    if ( defined(&mydatafnsort) ) {
        &mydatafnsort;
    } else {
        return $_[0] cmp $_[1];
    }
}

# Default DS filter when adding graph data sources from RRD files
$dsfilter_def = '';

# Names of defined RRAs and the names to use on the graphs
@rranames = ( 'MIN', 'AVERAGE', 'MAX', 'LAST' );
%rranames = ( 'MIN'     => 'Min',
              'AVERAGE' => 'Avg',
              'MAX'     => 'Max',
              'LAST'    => 'Last'
              );

# Viewer automatic refresh timer (seconds)
$vrefresh = '900';
# Minimum dashboard automatic refresh timer (seconds)
$drefresh = 1800;

# Default Views and their names
@dv_def  = ( 'end - 28 hours', 'end - 1 week', 'end - 1 month', 'end - 1 year' );
@dv_name = ( 'Past 28 Hours', 'Past Week', 'Past Month', 'Past Year' );
@dv_secs = ( 100800, 604800, 2419200, 31536000 );

# Default "Additonal GPRINTs" format
$gformat = "%8.2lf";

# Maximum time (seconds) CGI processes may be running (0 to disable)
$maxtime = 60;

# Cache refresh time (seconds)
$crefresh = 3600;

# Where to save data and store temporary files
$saved_dir = '/somewhere/drraw/saved';
$tmp_dir = '/somewhere/drraw/tmp';
# Whether or not RCS will be used
$use_rcs = 0;
# How often should the image cache be purged?
$clean_cache = 21600;

# Users
%users = ( 'guest' => 2 );

my $IndexMax = 0;
# Icons used in the browser
$icon_new = '/icons/generic.gif';
$icon_closed = '/icons/folder.gif';
$icon_open = '/icons/folder.open.gif';
$icon_text = '/icons/text.gif';
$icon_help = '/icons/unknown.gif';
$icon_bug = '/icons/bomb.gif';
$icon_link = '/icons/link.gif';

# Default colors
%colors = ( 'Black'  => '#000000',
            'Silver' => '#C0C0C0',
            'Gray'   => '#808080',
            'White'  => '#FFFFFF',
            'Maroon' => '#800000',
            'Red'    => '#FF0000',
            'Purple' => '#800080',
            'Fuchsia'=> '#FF00FF',
            'Green'  => '#008000',
            'Lime'   => '#00FF00',
            'Olive'  => '#808000',
            'Yellow' => '#FFFF00',
            'Orange' => '#FFA500',
            'Pink'   => '#FFC0CB',
            'Navy'   => '#000080',
            'Blue'   => '#0000FF',
            'Teal'   => '#008080',
            'Aqua'   => '#00FFFF',
            'Invisible' => '' );

# Style Sheet
$bgColor = '"#FFD89D"';
$CSS = <<END;
body {
        background: #FFD89D;
}
h1.title {
    border-bottom: 2px solid #FF9900;
    padding-bottom: 0.5em;
    text-align: center;
}
h1.title a {
    color: #FF9900;
}
.padless {
    margin-top: 0pt;
    margin-bottom: 0pt;
    padding: 0pt;
}
.small {
    font-family: Verdana, Arial, Helvetica, sans-serif;
    font-size: 10px;

}
.smallred {
    font-family: Verdana, Arial, Helvetica, sans-serif;
    font-size: 10px;
    color: red;
}
.normal {
    font-family: Verdana, Arial, Helvetica, sans-serif;
}
.red {
    font-family: Verdana, Arial, Helvetica, sans-serif;
    color: red;
}
.simplyred {
    color: red;
}
tr.header {
    background-color: #FF9900;
}
.code {
    font-family: Courier;
    font-size: 10px;
    background-color: #FFE2B7;
    border: 2pt #FF9900 solid;
    padding: 5pt;
}
.error {
    font-weight: bold;
    background-color: red;
    border: 2pt #FF9900 solid;
    padding: 5pt;
}
.help {
    color: Maroon;
    background: #E0E0E0;
    border: 1pt black solid;
    padding: 5pt;
}
div.tag {
    border-top: 2px solid #FF9900;
    margin-top: 1.5em;
    padding-top: 0.5em;
    font-size: 75%;
    text-align: right;
    color: #FF9900;
}

div.tag a {
    color: #FF9900;
}
END
$CSS2 = '';

# Mime Types
my %Mime = ( 'PNG' => 'image/png',
             'SVG' => 'image/svg+xml',
             'PDF' => 'application/pdf',
             'EPS' => 'application/postscript',
             'GIF' => 'image/gif' );
my @ImgFormat = ( 'PNG', 'SVG', 'PDF', 'EPS' );
@ImgFormat = ( 'PNG', 'GIF' ) if ( $RRDs::VERSION < 1.2 );

###############################################################################
##   STOP STOP STOP STOP STOP STOP STOP STOP STOP STOP STOP STOP STOP STOP   ##
##                                                                           ##
##          There should be no need for you to be subjected to the           ##
##           sight of the atrocious code found below these lines!            ##
##                                                                           ##
##   STOP STOP STOP STOP STOP STOP STOP STOP STOP STOP STOP STOP STOP STOP   ##
###############################################################################

# Now load the user configuration
unless ( do $config ) {
    my $err = ( $@ ne '' ) ? "$@" : "$!";
    print
        header(-status=>500),
        start_html('500 Internal error'),
        h2("Error loading configuration file: $config"),
        p($err),
        end_html;
    exit 1;
}

# Here is the earliest we can do this
if ( defined($ERRLOG) ) {
    # Abort violently so the user has no doubt about what's going on.
    open(STDERR, ">> $ERRLOG") or die "Failed to replace STDERR with \$ERRLOG($ERRLOG): $!\n";
}

# Make sure that %datadirs is defined
if ( !defined(%datadirs) ) {
    print
        header(-status=>500),
        start_html('500 Internal error'),
        h2("Invalid configuration: $config"),
        end_html;
    exit 1;
}
if ( scalar(grep(/\/$/, keys(%datadirs))) > 0 ) {
    print
        header(-status=>500),
        start_html('500 Internal error'),
        h2("Invalid \%datadirs in $config"),
        p("Remove any trailing / character in directory paths."),
        end_html;
    exit 1;
}

my $VERSION = '2.2b1';
my $REVISION = '$LastChangedRevision: 1526 $';  $REVISION =~ s/[^.0-9]+//g;
my $RELEASE = '1527'; # + 1
$VERSION = 'SVN-SNAPSHOT ['. $REVISION .' post '. $VERSION .']'
    unless ( $REVISION eq $RELEASE );
my ( $drraw_ID, $drraw_reported ) = ( time . $$, undef );
my $DEBUG = 0;

my %colorsidx = ();

# Additions require changes to &DRAW()
my @graphtypes = ( 'LINE1', 'LINE2', 'LINE3', 'LINE?', 'AREA', 'STACK', 'TICK',
                   'GPRINT', 'COMMENT',
                   'HRULE', 'VRULE',
                   'SHIFT',
                   '-Nothing-' );

my %goptions = ( 'gTitle'   => '--title',
                 'gVLabel'  => '--vertical-label',
                 'gBase'    => '--base',
                 'gUExp'    => '--units-exponent',
                 'gWidth'   => '--width',
                 'gHeight'  => '--height',
                 'gYUp'     => '--upper-limit',
                 'gYLow'    => '--lower-limit',
                 'gFormat'  => '--imgformat');

my $drrawhome = div({-class=>'tag'}, 'Brought to you by ',
                    a({-href=>'http://web.taranis.org/drraw/'}, em('drraw')),
                    '(version ' . $VERSION . ')');
                
# Little bit of JavaScript
my $IndexJS = <<END;
<script language="JavaScript">
function IndexClick(srcElement) {
 var iconElement, targetElement;
  iconElement = document.getElementById(srcElement.id + "-icon");
  targetElement = document.getElementById(srcElement.id + "-child");
  if (targetElement.style.display == "none") {
      targetElement.style.display = "";
      iconElement.src = "$icon_open";
  } else {
      targetElement.style.display = "none";
      iconElement.src = "$icon_closed";
  }
}
</script>
END

my $ViewerJS = <<END;
<script language="JavaScript">
var ViewerStart;
var ViewerRefresh;
function ViewerCountdown(target) {
  if (!ViewerStart || !ViewerRefresh)
      return;
  var targetElement;
  targetElement = document.getElementById(target);
  if (!targetElement)
      return;
  now = new Date();
  elapsed = ( now - ViewerStart ) / 1000;
  if (elapsed > ViewerRefresh + 30) {
      if ((elapsed - ViewerRefresh) % 2 < 1) {
          targetElement.innerHTML = "<b class=simplyred>Refresh failed, hit Reload</b>";
      } else {
          targetElement.innerHTML = "<b>Refresh failed, hit Reload</b>";
      }
      setTimeout("ViewerCountdown(thetarget)", 1000);
  } else if (elapsed > ViewerRefresh-1) {
      targetElement.innerHTML = "<b>Refreshing...</b>";
      setTimeout("ViewerCountdown(thetarget)", 10000);
  } else {
      with (Math) {
          min = floor((ViewerRefresh - elapsed) / 60);
          sec = floor((ViewerRefresh - elapsed) % 60);
          if (min < 1) {
              if (sec < 30 && sec % 2 == 0)
                  targetElement.innerHTML = "<b>Refreshing in "+ sec +"s</b>";
              else
                  targetElement.innerHTML = "Refreshing in "+ sec +"s";
          } else
              targetElement.innerHTML = "Refreshing in "+ min +"m "+ sec +"s";
      }
      thetarget=target;
      setTimeout("ViewerCountdown(thetarget)", 1000);
  }
}
</script>
END

my $EditorJS = <<END;
<script type="text/javascript">
// This should be callable onchange rather than onclick
// BUT Opera 7.23 does not implement this at all, and IE 6.0 handles it poorly.
function ShowHideChange(srcElement, target) {
  var targetElement = document.getElementById(target)
  if (!targetElement)
      return
  if (srcElement.value == "Show") {
      targetElement.style.display = "";
  } else if (srcElement.value == "Hide") {
      targetElement.style.display = "none"
  }
}

function ShowNew(srcElement, target) {
  var targetElement = document.getElementById("newrow" + target)
  if (!targetElement)
      return
  targetElement.style.display = "";
  srcElement.innerHTML = "Use the following row to " + srcElement.innerHTML.split("to", 2)[1]
}

function DisableToggle(target) {
  var DelElement = document.forms.Editor.elements.namedItem(target +"_DELETE")
  if (!DelElement)
      return
  var anElement
  var i = 0;
  while (anElement = document.forms.Editor.elements.item(i++)) {
      if (anElement.name.split("_", 1)[0] == target && anElement != DelElement)
          anElement.disabled = DelElement.checked
  }
  anElement = document.getElementById(target + "_Type")
  if (anElement) {
      TypeCB(anElement.value, target)
  }
}

function ToggleDB(target) {
  var DElement = document.forms.Editor.elements.namedItem(target +"_dname")
  if (!DElement)
      return
  var status = 0
  if (DElement.value.substring(0, 1) != "t")
      status = 1
  var anElement
  var i = 0
  while (anElement = document.forms.Editor.elements.item(i++)) {
      if (anElement.name.split("_", 1)[0] == target
          && anElement.name != DElement
          && anElement.name.split("_", 2)[1] != "DELETE"
          && anElement.name.split("_", 2)[1] != "Seq"
          && anElement.name.split("_", 2)[1] != "dname" ) {
          anElement.disabled = status
      }
  }
}

function TypeCB(value, target) {
  var targetElement = document.getElementById(target + "_Colorize");
  if (targetElement) {
      var ColorList = document.getElementById(target + "_Color");
      if (value == "PRINT" || value == "GPRINT" || value == "SHIFT"
          || value == "COMMENT" || value == "-Nothing-") {
          targetElement.bgColor = $bgColor
          if (ColorList) {
              ColorList.value = $bgColor
              ColorList.disabled = 1
          }
      } else if (ColorList)
          ColorList.disabled = 0
  }
  targetElement = document.getElementById(target + "_STACK");
  if (targetElement) {
      if (value.substring(0, 4) == "LINE" || value == "AREA") {
          targetElement.disabled = 0
      } else {
          targetElement.disabled = 1
      }
  }
  targetElement = document.getElementById(target + "_Width");
  if (targetElement) {
      if (value == "LINE?" || value == "TICK") {
          if (targetElement.disabled == 1) {
              if (value == "TICK") {
                  targetElement.value = "0.1"
              } else {
                  targetElement.value = "1"
              }
          }
          targetElement.disabled = 0
      } else {
          targetElement.value = "N/A"
          targetElement.disabled = 1
      }
  }
  return
}

function SetBgColor(src, target) {
  var targetElement = document.getElementById(target);
  if (!targetElement)
      return;
  if (!colors)
      return;
  var i = 0;
  while (i < colors.length) {
      if (colors[i++] == src.value)
          targetElement.bgColor = colors[i];
      i++;
  }
}

function ToggleXTRA(target) {
  var UserChoice;
  var i = 0;
  while (UserChoice = document.forms.Editor.XTRAS.item(i++)) {
      if (UserChoice.checked)
          break;
  }
  if (!UserChoice)
      return;
  var anElement;
  i = 0;
  while (anElement = document.forms.Editor.elements.item(i++)) {
      if ((anElement.name.split("_", 2)[1] == "XTRAS"
           && anElement.value == target)
          || (anElement.name.split("_", 2)[1] == "BR"
              && target == "BR")) {
          if (UserChoice.value == "On")
              anElement.checked = true;
          else if (UserChoice.value == "Off")
              anElement.checked = false;
          else if (UserChoice.value == "Not")
              anElement.checked = !anElement.checked;
      }
  }
}
</script>
END

my ( @rrdfiles, @evtfiles ) = ( (), () );
my ( %GraphsById, %TemplatesById, %BoardsById ) = ( (), (), () );
my %DS = ();
my ( %TMPL, %DBTMPL ) = ( (), () );

#
# Here's where the fun really starts.
# However, most of the code is in subroutines below.
#

$| = 1;

# Make sure CGI succeeded..
my $error = cgi_error;
if ($error) {
    print
        header(-status=>$error),
        start_html('Problems'),
        $header,
        h2('Request not processed'),
        strong($error),
        $footer,
        end_html;
    exit 1;
}

# We really can't do anything useful without these directories
if ( ! -d "${tmp_dir}" || ! -d "${saved_dir}"
     || ! -w "${tmp_dir}" || ! -w "${saved_dir}" ) {
    print
        header,
        start_html(-style=>{-code=>"<!--\n$CSS\n$CSS2\n-->\n\n"},
                   -title=>'drraw - '. $title),
        $header,
        h1({-class=>'title'}, a({-href=>&MakeURL}, $title)),
        h3('Configuration problem!'),
        p("The directories ${tmp_dir} and ${saved_dir} MUST exist and be writable by the CGI user!"),
        $footer,
        end_html;
    exit 1;
}

# Check whether RCS is available
if ( $use_rcs && -d "${saved_dir}/RCS" && -w "${saved_dir}/RCS" ) {
    if ( $use_rcs == 1 ) {
        eval { require Rcs; import Rcs qw(nonFatal); };
    } else {
        eval { require Rcs; import Rcs qw(nonFatal Verbose); };
    }
    if ( $@ ) {
        warn "Unable to load Rcs.pm: $@\n";
        $use_rcs = 0;
    }
} else {
    $use_rcs = 0;
}

# Check whether MD5 is available for caching
if ( $clean_cache ) {
    eval { require Digest::MD5; import Digest::MD5; };
    $clean_cache = 0 if ( $@ );
}

# Is this for an authenticated user?
my $user;
$user = &mygetuser
    if ( defined(&mygetuser) );
$user = $ENV{'REMOTE_USER'}
    if ( !defined($user) && defined($ENV{'REMOTE_USER'}) );
$user = 'guest'  if ( !defined($user) ); # Nope, let's default to "guest"

# Ok, we now have a username, what access should they get?
my $level;
if ( defined($users{$user}) ) {
    # User with custom level, including guest.
    $level = $users{$user};
} else {
    # Authenticated user defaults level 2 access
    $level = 2;
    # But let's bomb out if stupidity seems to be involved!
    die "SECURITY ALERT: 'guest' user entry is missing from %users configuration!\n" if ( $user eq 'guest' );
}

# Setup alarm timer to catch endless loops and other such problems
$SIG{'ALRM'} = sub { croak "Ran out of time, aborting!  (Consider raising \$maxtime [$maxtime])"; };
alarm($maxtime);

my @pnames = param();
if ( scalar(@pnames) == 0 || defined(param('Browse')) ) {
    #
    # The graph/template browser pages, by default.
    #
    print
        header,
        start_html(-style=>{-code=>$CSS}, -title=>'drraw - '. $title),
        $header,
        h1({-class=>'title'}, a({-href=>&MakeURL}, $title));

    # Browse=Help -> Display help page
    if ( defined(param('Browse')) && param('Browse') eq 'Help' ) {
        &HELP;
        exit 0;
    }

    # Browse=Log -> Display log page
    if ( defined(param('Browse')) && param('Browse') eq 'Log' ) {
        &ShowLog;
        exit 0;
    }

    # Browse=Rcs -> Display RCS log page
    if ( defined(param('Browse')) && param('Browse') eq 'Rcs' ) {
        &ShowRcsLog;
        exit 0;
    }

    if ( $level > 0 ) {
        print
            a({-href=>MakeURL('USERSAID', 'New')},
              img({-src=>$icon_new, -border=>0}), ' Create a new graph'), br;
    }

    &Indexes_Load;
    if ( $level > 0 ) {
        print
            a({-href=>MakeURL('USERSAID', 'New', 'Type', 'DB')},
              img({-src=>$icon_new, -border=>0}), ' Define a new dashboard'),br
              if ( scalar(keys(%GraphsById))+scalar(keys(%TemplatesById)) >0 );
    }

    print $IndexJS;
    &CustomIndex(\%Index, '') if ( defined(%Index) );

    if ( defined(param('Browse')) && param('Browse') eq 'AllGraphs' ) {
        print
            '<div id="AllGraphs" style="cursor:pointer" onclick="IndexClick(this)">',
            img({-src=>$icon_open, -border=>0, -id=>'AllGraphs-icon'}),
            ' All Graphs (' . scalar(keys(%GraphsById)) . ')',
            '</div>',
            '<div id="AllGraphs-child">',
            '<blockquote class=padless>',
            start_table{-width=>'100%'};
        &GraphIndex('.');
        print
            end_table,
            '</blockquote></div>';
    } else {
        print
            a({-href=>MakeURL('Browse', 'AllGraphs')},
              img({-src=>$icon_closed, -border=>0}),
              ' All Graphs (' . scalar(keys(%GraphsById)) . ')'), br;
    }

    if ( defined(param('Browse')) && param('Browse') eq 'AllTemplates' ) {
        # Templates list
        print
            '<div id="AllTemplates" style="cursor:pointer" onclick="IndexClick(this)">',
            img({-src=>$icon_open, -border=>0, -id=>'AllTemplates-icon'}),
            ' All Templates (' . scalar(keys(%TemplatesById)) . ')',
            '</div>',
            '<div id="AllTemplates-child">',
            '<blockquote class=padless>',
            start_table{-width=>'100%'};
        &Cache_Load;
        &TemplateIndex('.');
        print
            end_table,
            '</blockquote></div>';
    } else {
        print
            a({-href=>MakeURL('Browse', 'AllTemplates')},
              img({-src=>$icon_closed, -border=>0}),
              ' All Templates (' . scalar(keys(%TemplatesById)) . ')'), br;
    }

    if ( defined(param('Browse')) && param('Browse') eq 'AllDashboards' ) {
        # Dashboards list
        print
            '<div id="AllDashboards" style="cursor:pointer" onclick="IndexClick(this)">',
            img({-src=>$icon_open, -border=>0, -id=>'AllDashboards-icon'}),
              ' All Dashboards (' . scalar(keys(%BoardsById)) . ')',
            '</div>',
            '<div id="AllDashboards-child">',
            '<blockquote class=padless>',
            start_table{-width=>'100%'};
        &Cache_Load;
        &DashBoardIndex('.');
        print
            end_table,
            '</blockquote></div>';
    } else {
        print
            a({-href=>MakeURL('Browse', 'AllDashboards')},
              img({-src=>$icon_closed, -border=>0}),
              ' All Dashboards (' . scalar(keys(%BoardsById)) . ')'), br;
    }

    if ( $level > 0 ) {
        # Link to log page
        print
            a({-href=>MakeURL('Browse', 'Log')},
              img({-src=>$icon_text, -border=>0}), ' ChangeLog'), br;
        # Link to help page
        print
            a({-target=>'drrawhelp', -href=>MakeURL('Browse', 'Help')},
              img({-src=>$icon_help, -border=>0}), ' Help'), br;
    }

    if ( $level > 1 ) {
        # For level 2 users,
        #	"Report a bug" link
        #   "Add this installation to the compatibility report" link
        print
            a({-target=>'drrawhelp',
               href=>MakeURL('Browse', 'Help') . '#contact'},
              img({-src=>$icon_bug, -border=>0}), ' Report a bug, ...'), br;

        $drraw_ID = param('Reported')
            if ( defined(param('Reported')) );

        my $query = new CGI;
        $query->delete_all;
        $query->param('drrawver', "$VERSION");
        $query->param('perlver', "$]");
        $query->param('cgiver', "$CGI::VERSION");
        $query->param('rrdver', "$RRDs::VERSION");
        $query->param('osname', "$^O");
        $query->param('websvr', $ENV{'SERVER_SOFTWARE'});
        $query->param('id', $drraw_ID);

        my $status;
        if ( defined(param('Reported')) ) {
            $drraw_reported = $query->query_string();
            &Indexes_Save('drraw', $drraw_ID, $drraw_reported);
        } else {
            if ( defined($drraw_reported) ) {
                $status = b("out of date")
                    if ( $drraw_reported ne $query->query_string() );
            } else {
                $status = b("unreported");
            }

            $query->param('url', url());
            print
                a({-href=>"http://web.taranis.org/cgi-bin/drraw-report.pl?"
                       . $query->query_string()},
                  img({-src=>$icon_link, -border=>0}),
                  ' Add this installation to the compatibility report.'),
                ' Status: '. $status
                if ( defined($status) && scalar(keys(%GraphsById)) + scalar(keys(%TemplatesById)) > 5 && $REVISION eq $RELEASE );
        }
    }

    print
        $drrawhome,
        $footer,
        end_html;
} elsif ( (defined(param('Graph')) || defined(param('Template'))
            || defined(param('Dashboard')) ) && defined(param('Mode')) ) {
    #
    # Displaying, without any editing..
    #
    if ( param('Mode') eq 'view' ) {
        # Mode=view -> HTML page
        &Indexes_Load;
        # Make sure that whatever was requested exists..
        if ( defined(param('Graph'))
             && !defined($GraphsById{param('Graph')}) ) {
            print
                header,
                start_html({-style=>{-code=>$CSS}, -title=>'drraw - '. $title}),
                $header,
                h1('Invalid Request.'),
                p('Sorry, the graph you requested (' . param('Graph') 
                  . ') does not appear in the index.');
        } elsif ( defined(param('Template'))
                  && !defined($TemplatesById{param('Template')}) ) {
            print
                header,
                start_html({-style=>{-code=>$CSS}, -title=>'drraw - '. $title}),
                $header,
                h1('Invalid Request.'),
                p('Sorry, the template you requested (' . param('Template') 
                  . ') does not appear in the index.');
        } elsif ( defined(param('Template')) && !defined(param('Base')) ) {
            # Template, but no base specified, display choices on a single page
            &Cache_Load;
            &TMPLFind($TemplatesById{param('Template')}{'Filter'},
                      $TemplatesById{param('Template')}{'Display'});
            print
                header,
                start_html({-style=>{-code=>$CSS}, -title=>'drraw - '
                                . $TemplatesById{param('Template')}{'Name'}}),
                $header,
                h1($TemplatesById{param('Template')}{'Name'}),
                start_form({-align=>'center', -method=>'GET'}),
                '<input type=hidden name=Template value=' . param('Template') . " />\n",
                p({-align=>'center'},
                  scrolling_list(-name=>'Base',
                                 -multiple=>'true',
                                 -values=>[sort { $TMPL{$a} cmp $TMPL{$b} }
                                           keys(%TMPL)],
                                 -labels=>\%TMPL,
                                 -size=>(scalar(keys(%TMPL)) < 25) ?
                                 scalar(keys(%TMPL)) : 25),
                  submit(-name=>'Mode', -value=>'view', -valign=>'center')),
                end_form;
        } elsif ( defined(param('Dashboard'))
                  && !defined($BoardsById{param('Dashboard')}) ) {
            print
                header,
                start_html({-style=>{-code=>$CSS}, -title=>'drraw - '. $title}),
                $header,
                h1('Invalid Request.'),
                p('Sorry, the dashboard you requested (' .
                  param('Dashboard') . ') does not appear in the index.');
        } elsif ( defined(param('Dashboard')) ) {
            # Dashboard template, but no base specified, display choices
            # on a single page
            &Cache_Load;
            &BoardFind(param('Dashboard'));
            if ( scalar(keys(%DBTMPL)) > 0 && !defined(param('Base')) ) {
                print
                    header,
                    start_html({-style=>{-code=>$CSS}, -title=>'drraw - '
                            . $BoardsById{param('Dashboard')}{'Name'}}),
                    $header,
                    h1($BoardsById{param('Dashboard')}{'Name'}),
                    p({-align=>'center'},
                      start_form({-align=>'center', -method=>'GET'}),
      '<input type=hidden name=Dashboard value='. param('Dashboard') ." />\n",
                      scrolling_list(-name=>'Base',
                                     -values=>[sort keys(%DBTMPL)],
                                     -size=>(scalar(keys(%DBTMPL)) < 25) ?
                                     scalar(keys(%DBTMPL)) : 25),
                      submit(-name=>'Mode', -value=>'view', -valign=>'center'),
                      end_form);
            } else {
                #
                # Dashboard display page
                #

                # Save what we need later on
                my $board = param('Dashboard');
                my $base = param('Base');
                my $filter = ( defined(param('Filter')) ) ? param('Filter'):'';
                my $view = param('View');
                my $start = param('Start');
                my $end = param('End');

                # Load dashboard definition
                &Definition_Load('d' . param('Dashboard'));

                # Find out which view to use
                if ( !defined($view) ) {
                    $view = 0;
                    while ( $view < scalar(@dv_name) ) {
                        last if ( $dv_name[$view] eq param('dView') );
                        $view += 1;
                    }
                    $view = 0 if ( $view >= scalar(@dv_name) );
                }
                param(-name=>'View', -value=>$view);
                if ( !defined($start) || !defined($end) ) {
                    param(-name=>'Start', -value=>$dv_def[$view]);
                    param(-name=>'End', -value=>'now');
                } else {
                    param(-name=>'Start', -value=>$start);
                    param(-name=>'End', -value=>$end);
                    $view = -1;
                }

                # Dashboards are somewhat special in the sense that they will
                # potentially cause _many_ graphs to be drawn.  As this is
                # typically done for relatively long period of times and
                # small(er) images, there really is little point in having a
                # short page refresh to get many cached images, so let's spare
                # the server.
                my ( $startts, $endts ) = ( 0, time );
                my $ttl = 1800; # 30m Default for dashboards
                if ( defined(&RRDs::times) ) {
                    ( $startts, $endts ) = RRDs::times(param('Start'),
                                                       param('End'));

                    die "Invalid result ($startts) from RRDs::times()\n"
                        unless ( $startts =~ /(\d+)/ );
                    $startts = $1; # Untaint
                    die "Invalid result ($endts) from RRDs::times()\n"
                        unless ( $endts =~ /(\d+)/ );
                    $endts = $1; # Untaint

                    if ( defined(param('dWidth'))
                         && param('dWidth') =~ /(\d+)/ ) {
                        $ttl = int(($endts - $startts + 1) / $1); # Untaint
                    } else {
                        $ttl = int(($endts - $startts + 1) / 400 );
                    }
                } else {
                    if ( $view < scalar(@dv_secs) ) {
                        if ( defined(param('dWidth'))
                             && param('dWidth') =~ /(\d+)/ ) {
                            $ttl = int($dv_secs[$view] / $1); # Untaint
                        } else {
                            $ttl = int($dv_secs[$view] / 400);
                        }
                    }
                }
                # The above calculations can result in short refresh times
                # depends on how sensible the configuration and user settings
                # are.
                $ttl = $drefresh unless ( $ttl > $drefresh );

                # Header
                print
                    header,
                    start_html({-style=>{-code=>$CSS}, -title=>'drraw - '
                                    . $BoardsById{$board}{'Name'},
                                    -head=>meta({-http_equiv=>'Refresh',
                                                 -content=>$ttl})}),
                    $header,
                    $ViewerJS,
                    start_table({-width=>'100%', -border=>0}),
                    Tr(td(h1($BoardsById{$board}{'Name'})),
                       td({-align=>'right', -valign=>'top'},
                          a({-href=>&MakeURL()}, b('[Home]')),
                          ( $level > 0 ) ? 
                          a({-href=>&MakeURL('USERSAID', 'Edit', 'Type', 'DB',
                                             'Dashboard', $board)},
                            '[Edit]') : '',
                          ( -e $saved_dir .'/RCS/d'. $board .',v' )
                          ? a({-href=>MakeURL('Browse', 'Rcs',
                                              'Id', 'd'. $board)},
                              '[RCS Log]') : '',
                          br, small(b("". strftime("%Y-%m-%d %H:%M:%S",
                                                   localtime))),
                          br,
                          small({-id=>'counter'}))),
                    end_table,
                    '<script type="text/javascript">',
                    'ViewerStart = new Date(); ViewerRefresh = '. $ttl .';',
                    'setTimeout("ViewerCountdown(\'counter\')", 1000);',
                    '</script>',
                    '<p align=center>';

                # Display the view chooser
                $view = 0;
                while ( $view < scalar(@dv_name) ) {
                    if ( $view == param('View') ) {
                        print
                            b(' [' . $dv_name[$view] . '] ');
                    } else {
                        print
                            a({-href=>&MakeURL('Mode', 'view',
                                               'Dashboard', $board,
                                               'Base', $base,
                                               'View', $view,
                                               'Filter', $filter)},
                              ' [' . $dv_name[$view] . '] ');
                    }
                    $view += 1;
                }

                # Display custom view entry fields
                print
                    '</p>',
                    start_form({-align=>'center', -method=>'GET'}),
                    '<p align="center">',
                    '<input type=hidden name=View value=-1 />',
                    '<input type=hidden name=Dashboard value='. $board .' />',
                    'Start: ', textfield(-name=>'Start'),
                    'End: ', textfield(-name=>'End');
                if ( defined($base) ) {
                    print
                        '<input type=hidden name=Base value="'. $base .'" />';
                } else {
                    print
                        '<br>',
                        'Filter: ',
                        textfield(-name=>'Filter', -value=>$filter),
                        '<br>';
                }
                print
                    submit(-name=>'Mode', -value=>'view'),
                    '</p>',
                    end_form;

                # Validate Start/End times
                if ( defined(&RRDs::times) ) {
                    RRDs::times(( defined(param('Start'))
                                  && param('Start') ne '' )
                                ? param('Start') : 'end - 1 day',
                                ( defined(param('End'))
                                  && param('End') ne '' )
                                ? param('End') : 'now' );
        
                    if ( defined(RRDs::error) ) {
                        &Error(RRDs::error);
                    }
                }

                # Start dashboard table
                print
                    '<p align="center">';
                print
                    b(strftime("%a %Y-%m-%d&nbsp;%H:%M",
                               localtime($startts))
                      .'&nbsp;-&nbsp;'.
                      strftime("%a %Y-%m-%d&nbsp;%H:%M",
                               localtime($endts)))
                      if ( param('View') == -1 && $startts > 0 );
                print
                    start_table({-border=>0,-align=>'center'});

                if ( !defined(param('dGrouped')) ) {
                    # Standard dashboard layout algorithm
                    print '<tr>';
                    my $col = 1;
                    my $item;
                    foreach $item ( sort { param($a) <=> param($b) }
                                    grep(/^[a-z]+_Seq$/, param()) ) {
                        $item =~ s/_Seq//;
                        next if ( defined(param("${item}_DELETE")) );
                        if ( param("${item}_dname") =~ /^g/ ) {
                            print
                                td(a({-href=>MakeURL('Mode', 'view',
                                                     'Graph', substr(param("${item}_dname"), 1))},
                                     &GraphHTML(param("${item}_dname"), undef,
                                                param('Start'), param('End'),
                                                param('dWidth'),
                                                param('dHeight'),
                                                ( param('dNoLegend') eq 'On' )
                                                ? 1 : undef)));
                        } elsif ( param("${item}_dname") =~ /^t/ ) {
                            my $titem = param("${item}_dname");
                            $titem =~ s/^t//;
                            my @list = ();
                            if ( param("${item}_type") eq 'List' ) {
                                &TMPLFind($TemplatesById{$titem}{'Filter'},
                                          $TemplatesById{$titem}{'Display'});
                                foreach ( param("${item}_list") ) {
                                    push @list, $_
                                        if ( $filter eq ''
                                             || $TMPL{$_} =~ /$filter/ );
                                }
                            } elsif ( param("${item}_type") eq 'Regex' ) {
                                &TMPLFind($TemplatesById{$titem}{'Filter'},
                                          $TemplatesById{$titem}{'Display'});
                                my $rx = param("${item}_regex");
                                foreach ( keys(%TMPL) ) {
                                    push @list, $_
                                        if ( $TMPL{$_} =~ /$rx/
                                             && ( $filter eq ''
                                                  || $TMPL{$_} =~ /$filter/ ));
                                }
                            } elsif ( param("${item}_type") eq 'All' ) {
                                &TMPLFind($TemplatesById{$titem}{'Filter'},
                                          $TemplatesById{$titem}{'Display'});
                                foreach ( sort { $TMPL{$a} cmp $TMPL{$b} }
                                          keys(%TMPL) ) {
                                    push @list, $_
                                        if ( $filter eq ''
                                             || $TMPL{$_} =~ /$filter/ );
                               }
                            } elsif ( param("${item}_type") eq 'Base' ) {
                                @list = ( $DBTMPL{$base}{$item} )
                                    if ( defined($DBTMPL{$base}{$item}) );
                            } else {
                                print
                                    td({-align=>'center'},
                                       em('Error in dashboard definition'));
                                $col += 1;
                            }

                            while ( scalar(@list) > 0 ) {
                                print
                                    td(a({-href=>MakeURL('Mode', 'view',
                                                         'Template', $titem,
                                                         'Base', $list[0])},
                                         &GraphHTML(param("${item}_dname"),
                                                    $list[0], param('Start'),
                                                    param('End'),
                                                    param('dWidth'),
                                                    param('dHeight'),
                                                    ( param('dNoLegend') eq 'On' ) ? 1 : undef)));
                                shift @list;
                                $col += 1;
                                if ( $col > param('dCols') ) {
                                    print '</tr><tr>';
                                    $col = 1;
                                }
                            }
                            $col -= 1;
                        } else {
                            print
                                td({-align=>'center'},
                                   em('Error in dashboard definition'));
                        }
                        $col += 1;
                        if ( $col > param('dCols') ) {
                            print '</tr><tr>';
                            $col = 1;
                        }
                    }
                    print '</tr>';
                } else {
                    # Dashboard 'Grouped' layout algorithm
                    my ( %Rows, @cols );
                    my $item;
                    my $titem;
                    foreach $item ( sort { param($a) <=> param($b) }
                                    grep(/^[a-z]+_Seq$/, param()) ) {
                        $item =~ s/_Seq//;
                        next if ( defined(param("${item}_DELETE")) );
                        my @list = ();
                        if ( param("${item}_dname") =~ /^t/ ) {
                            $titem = param("${item}_dname");
                            $titem =~ s/^t//;
                            &TMPLFind($TemplatesById{$titem}{'Filter'},
                                      $TemplatesById{$titem}{'Display'});
                            if ( param("${item}_type") eq 'List' ) {
                                foreach ( param("${item}_list") ) {
                                    push @list, $_
                                        if ( $filter eq ''
                                             || $TMPL{$_} =~ /$filter/ );
                                }
                            } elsif ( param("${item}_type") eq 'Regex' ) {
                                my $rx = param("${item}_regex");
                                foreach ( keys(%TMPL) ) {
                                    push @list, $_
                                        if ( $TMPL{$_} =~ /$rx/
                                             && ( $filter eq ''
                                                  || $TMPL{$_} =~ /$filter/ ));
                                }
                            } elsif ( param("${item}_type") eq 'All' ) {
                                foreach ( keys(%TMPL) ) {
                                    push @list, $_
                                        if ( $filter eq ''
                                             || $TMPL{$_} =~ /$filter/ );
                                }
                            } else {
                                &Error('Error in dashboard definition');
                            }
                        } else {
                            &Error('Error in dashboard definition');
                            next;
                        }

                        push @cols, $titem;

                        foreach ( @list ) {
                            my $nex = param("${item}_row");
                            my $row;
                            if ( $nex eq '' ) {
                                $row = $TMPL{$_};
                            } else {
                                # Why ' - ' ?  Is it okay?
                                $row = join(' - ', ($TMPL{$_} =~ /$nex/));
                            }
                            $Rows{$row}[scalar(@cols)-1] = $_;
                        }
                    }

                    my $row;
                    foreach $row ( sort(keys(%Rows)) ) {
                        print '<tr>';
                        my $col = 0;
                        while ( $col < scalar(@cols) ) {
                            print '<td>';
                            if ( defined($Rows{$row}[$col]) ) {
                                print
                                    td(a({-href=>MakeURL('Mode', 'view',
                                                         'Template',
                                                         $cols[$col],
                                                         'Base',
                                                         $Rows{$row}[$col])},
                                         &GraphHTML('t'. $cols[$col],
                                                    $Rows{$row}[$col],
                                                    param('Start'),
                                                    param('End'),
                                                    param('dWidth'),
                                                    param('dHeight'),
                                                    ( param('dNoLegend') eq 'On' ) ? 1 : undef)));
                            } else {
                                print td('N/A');
                            }
                            $col += 1;
                        }
                        print '</tr>';
                    }
                }
                print
                    end_table;
            }
        } else {
            #
            # Graph/template display page
            #
            print
                header;
            my $id;
            if ( defined(param('Graph')) ) {
                # Graph header
                $id = 'g' . param('Graph');
                print
                    start_html({-style=>{-code=>$CSS}, -title=>'drraw - '
                                    . $GraphsById{param('Graph')}{'Name'},
                                    -head=>meta({-http_equiv=>'Refresh',
                                                 -content=>$vrefresh})}),
                    $header,
                    $ViewerJS,
                    start_table({-width=>'100%', -border=>0}),
                    Tr(td(h1(a({-href=>&MakeURL('Mode', 'view',
                                                'Graph', param('Graph'))},
                               $GraphsById{param('Graph')}{'Name'}))),
                       td({-align=>'right', -valign=>'top'},
                          a({-href=>&MakeURL()}, b('[Home]')),
                          ( $level > 0 ) ?
                          a({-href=>&MakeURL('USERSAID', 'Edit',
                                             'Graph', param('Graph'))},
                            '[Edit]') : '',
                          ( -e $saved_dir .'/RCS/g'. param('Graph') .',v' )
                          ? a({-href=>MakeURL('Browse', 'Rcs',
                                              'Id', 'g'. param('Graph'))},
                              '[RCS Log]') : '',
                          br, small(b("". strftime("%Y-%m-%d %H:%M:%S",
                                                   localtime))),
                          br,
                          small({-id=>'counter'}))),
                    end_table,
                    '<script type="text/javascript">',
                    'ViewerStart = new Date(); ViewerRefresh = '. $vrefresh .';',
                    'setTimeout("ViewerCountdown(\'counter\')", 1000);',
                    '</script>';
            } else {
                # Template header
                &Cache_Load;
                &TMPLFind($TemplatesById{param('Template')}{'Filter'},
                          $TemplatesById{param('Template')}{'Display'});
                $id = 't' . param('Template');
                my $title = '';
                $title = ': '. a({-href=>&MakeURL('Mode', 'view',
                                                  'Template',param('Template'),
                                                  'Base', param('Base'))},
                                 $TMPL{param('Base')})
                    if ( scalar([param('Base')]) == 1 );
                print
                    start_html({-style=>{-code=>$CSS}, -title=>'drraw - '
                            . $TemplatesById{param('Template')}{'Name'},
                                    -head=>meta({-http_equiv=>'Refresh',
                                                 -content=>$vrefresh})}),
                    $header,
                    $ViewerJS,
                    start_table({-width=>'100%', -border=>0}),
                    Tr(td(h1(a({-href=>&MakeURL('Mode', 'view',
                                                'Template',param('Template'))},
                               $TemplatesById{param('Template')}{'Name'})
                             . $title)),
                       td({-align=>'right', -valign=>'top'},
                          a({-href=>&MakeURL()}, b('[Home]')),
                          ( $level > 0 ) ?
                          a({-href=>&MakeURL('USERSAID', 'Edit',
                                             'Template', param('Template'))},
                            '[Edit]') : '',
                          ( -e $saved_dir .'/RCS/t'. param('Template') .',v' )
                          ? a({-href=>MakeURL('Browse', 'Rcs',
                                              'Id', 't'. param('Template'))},
                              '[RCS Log]') : '',
                          br, small(b("". strftime("%Y-%m-%d %H:%M:%S",
                                                   localtime))),
                          br,
                          small({-id=>'counter'}))),
                    end_table,
                    '<script type="text/javascript">',
                    'ViewerStart = new Date(); ViewerRefresh = '. $vrefresh .';',
                    'setTimeout("ViewerCountdown(\'counter\')", 1000);',
                    '</script>',
                    start_form({-align=>'center', -method=>'GET'}),
                    '<input type=hidden name=Template value=' . param('Template') . " />\n",
                    p({-align=>'center'},
                      popup_menu(-name=>'Base',
                                 -values=>[sort { $TMPL{$a} cmp $TMPL{$b} }
                                           keys(%TMPL)],
                                 -labels=>\%TMPL),
                      submit(-name=>'Mode', -value=>'view')),
                    end_form;
            }

            # Validate trending specification
            my ( $trendshift, $trendcount ) = ( 0, 1 );
            if ( ( defined(param('Shift')) && param('Shift') ne '' )
                 || ( defined(param('Count')) && param('Count') ne '' ) ) {
                if ( param('Shift') =~ /^[-+]/ ) {
                    $trendshift = param('Shift');
                    $trendcount = param('Count');
                } else {
                    &Error("Shift must begin with + or -");
                }
            }
            # Display the view chooser
            my $view = 0;
            print '<p align=center>'
                if ( defined(param('View'))
                     || $trendshift > 0
                     || scalar(@{[param('Base')]}) > 1 );
            while ( $view < scalar(@dv_def) ) {
                if ( defined(param('View'))
                     || $trendshift > 0
                     || scalar(@{[param('Base')]}) > 1 ) {
                    # View Links, single graph will follow
                    if ( defined(param('View')) && $view == param('View') ) {
                        print
                            b(' [' . $dv_name[$view] . '] ');
                    } elsif ( defined(param('Graph')) ) {
                        print
                            a({-href=>&MakeURL('Mode', 'view',
                                               'Graph', param('Graph'),
                                               'View', $view)},
                              ' [' . $dv_name[$view] . '] ');
                    } else {
                        print
                            a({-href=>&MakeURL('Mode', 'view',
                                               'Template', param('Template'),
                                               'Base', [ param('Base') ],
                                               'View', $view)},
                              ' [' . $dv_name[$view] . '] ');
                    }
                } else {
                    # Graphs Links for the pre-defined views
                    print
                        p({-align=>'center'},
                          a({-href=>MakeURL('Mode', 'view',
                                            ( defined(param('Graph')) )
                                            ? ( 'Graph', param('Graph') )
                                            : ( 'Template', param('Template'),
                                                'Base', param('Base') ),
                                            'View', $view)},
                            &GraphHTML($id, ( defined(param('Base')) ) ?
                                       param('Base') : undef, $dv_def[$view])),
                          br,
                          b($dv_name[$view]));
                }
                $view += 1;
            }

            # If this is a pre-defined view, define Start/End
            if ( defined(param('View')) ) {
                print '</p>';

                if ( param('View') >= 0 ) {
                    param('Start', $dv_def[param('View')]);
                    param('End', 'now');
                }
            }

            # Display the custom view (Start/End) entry fields
            print
                start_form({-align=>'center', -method=>'GET'}),
                '<input type=hidden name=View value=-1 />',
                ( defined(param('Graph')) ) ?
                '<input type=hidden name=Graph value='
                . param('Graph') . ' />'
                : '<input type=hidden name=Template value='
                . param('Template') . ' />';
            if ( defined(param('Base')) && scalar(@{[param('Base')]}) > 0 ) {
                foreach ( param('Base') ) {
                    print "<input type='hidden' name='Base' value='$_' />";
                }
            }
            my $trend = '';
            $trend = '<br>'.
                     'Shift: '. textfield(-name=>'Shift').
                     ' Count: '. textfield(-name=>'Count', -size=>2)
                     if ( defined(param('View')) );
            print
                p({-align=>'center'},
                  'Start: ', textfield(-name=>'Start'),
                  'End: ', textfield(-name=>'End'),
                  submit(-name=>'Mode', -value=>'view'),
                  $trend),
                end_form;

            # Validate Start/End times
            my $start = ( defined(param('Start')) && param('Start') ne '' )
                ? param('Start') : 'end - 1 day';
            my $end   = ( defined(param('End')) && param('End') ne '' )
                ? param('End') : 'now';
            my ( $startts, $endts );
            if ( defined(&RRDs::times) ) {
                ( $startts, $endts ) = RRDs::times($start, $end);
                if ( defined(RRDs::error) ) {
                    &Error(RRDs::error);
                }
            }

            # Links to graphs
            if ( defined(param('Base')) && scalar(@{[param('Base')]}) > 1 ) {
                # Dashboard style display for templates
                foreach ( param('Base') ) {
                    print
                        p({-align=>'center'},
                          a({-href=>MakeURL('Mode', 'view',
                                            'Template', param('Template'),
                                            'Base', $_)},
                            &GraphHTML($id, $_, param('Start'), param('End'))));
                }
            } elsif ( defined(param('View')) ) {
                # Single graph view, or trend page
                while ( $trendcount-- > 0 ) {
                    print
                        '<p align=center>',
                        &GraphHTML($id, ( defined(param('Base')) ) ?
                                   param('Base') : undef, $start, $end),
                        br;
                    if ( param('View') >= 0 ) {
                        print
                            b($dv_name[param('View')]);
                    } else {
                        if ( defined(&RRDs::times) ) {
                            print
                                b(strftime("%a %Y-%m-%d&nbsp;%H:%M",
                                           localtime($startts))
                                  .'&nbsp;-&nbsp;'.
                                  strftime("%a %Y-%m-%d&nbsp;%H:%M",
                                           localtime($endts)));
                        } else {
                            print
                                b('Custom View');
                        }
                    }
                    print
                        '</p>';
                    # Trend adjustments
                    if ( $trendcount > 0 ) {
                        $start .= " $trendshift" unless ( $start =~ /end/ );
                        $end   .= " $trendshift" unless ( $end =~ /start/ );
                        if ( defined(&RRDs::times) ) {
                            ( $startts, $endts ) = RRDs::times($start, $end);
                            if ( defined(RRDs::error) ) {
                                &Error(RRDs::error);
                                last;
                            }
                            last if ( $startts > time() );
                        }
                    }
                }
            }
        }
        print
            $drrawhome,
            $footer,
            end_html;
    } elsif ( param('Mode') eq 'show' ) {
        # Mode=show -> Image
        my @graph = ( ( defined(param('Graph')) ) ? param('Graph')
                      : param('Template'),
                      ( defined(param('Base')) ) ? param('Base') : undef,
                      ( defined(param('Start')) ) ? param('Start') : undef,
                      ( defined(param('End')) ) ? param('End') : undef,
                      ( defined(param('Width')) ) ? param('Width') : undef,
                      ( defined(param('Height')) ) ? param('Height') : undef,
                      ( defined(param('NoLegend')) ) ? param('NoLegend') : undef,
                      ( defined(param('Format')) ) ? param('Format') : undef);

        &Definition_Load($graph[0]);
        unlink "${tmp_dir}/$1" if ( $graph[0] =~ /^(\d+\.\d+)$/ ); # Untaint
        # The following were just lost
        param(-name=>'Mode', -value=>'show'); # For &Error
        param(-name=>'Graph', -value=>$graph[0]) unless ( $graph[0] =~ /^t/ );
        param(-name=>'Template', -value=>$graph[0]) if ( $graph[0] =~ /^t/ );
        param(-name=>'Base', -value=>$graph[1]) if ( defined($graph[1]) );
        param(-name=>'Start', -value=>$graph[2]);
        param(-name=>'End', -value=>$graph[3]);
        param(-name=>'gWidth', -value=>$graph[4]) if ( defined($graph[4]) );
        param(-name=>'gHeight', -value=>$graph[5]) if ( defined($graph[5]) );
        param(-name=>'gNoLegend', -value=>1) if ( defined($graph[6]) );
        param(-name=>'gFormat', -value=>$graph[7]) if ( defined($graph[7]) );
        &DSLoad;
        &Cache_Load;
        &Sort_Colors_Init;
        if ( $graph[0] =~ /^t/ ) {
            # Define graph from the template definition and chosen base
            my %tDB;
            my ( $ds, @subs );
            foreach $ds ( sort keys(%DS) ) {
                next if ( param("${ds}_File") eq '' );
                if ( defined($tDB{param("${ds}_File")}) ) {
                    param("${ds}_File", $tDB{param("${ds}_File")});
                } else {
                    die "Bad Template [${ds}]\n"
                        unless ( defined(param("${ds}_Tmpl")));
                    if ( !defined(param("${ds}_Formula")) ) {
                        $tDB{param("${ds}_File")} = param("${ds}_File");
                        $tDB{param("${ds}_File")} =~ s/\/\/.*$/\/\//;
                    } else {
                        $tDB{param("${ds}_File")} = '';
                    }
                    if ( scalar(@subs) == 0 ) {
                        my $dbname = param('Base');
                        $dbname =~ s/^.*\/\///;
                        my $ex = param('tRegex');
                        @subs = ( $dbname =~ /$ex/ );
                    }
                    $tDB{param("${ds}_File")} .= &ExpandMatches(param("${ds}_Tmpl"), \@subs );
                    if ( !defined(param("${ds}_Formula")) ) {
                        foreach ( sort keys %datadirs ) {
                            $tDB{param("${ds}_File")} =~ s/^.*\/\//$_\/\//;
                            last if ( -r $tDB{param("${ds}_File")} );
                        }
                    }
                    param(-name=>"${ds}_File",
                          -value=>$tDB{param("${ds}_File")});
                }
            }

            &TMPLFind(param('tRegex'), param('tNiceRegex'));

            param(-name=>'gTitle', -value=>param('gTitle')
                  .': '. $TMPL{param('Base')});
        }
        # Finally, produce the image
        &DRAW(( $graph[0] =~ /^\d/ ) ? 1 : 2);
    } else {
        # Catch all for invalid requests
        print
            header,
            start_html({-style=>{-code=>$CSS}, -title=>'drraw - '. $title}),
            $header,
            h1('Invalid Request.'),
            $footer,
            end_html;
    }
} elsif ( defined(param('Type')) && param('Type') eq 'DB' ) {
    #
    # DashBoard editing pages
    #

    if ( $level == 0 ) {
        print
            header(-status=>'Permission Denied'),
            start_html('Permission Denied'),
            $header,
            h2('Permission Denied');
        exit 0;
    }

    &Indexes_Load;
    &Cache_Load;

    # Avoids having to check whether it's defined below..
    param('USERSAID', 'New') unless ( defined(param('USERSAID')) );

    # Deleting a saved definition
    if ( param('USERSAID') eq 'Delete' ) {
        if ( defined(param('Dashboard'))
             && param('Dashboard') =~ /^(\d+\.\d+)$/ ) {
            if ($level == 0
                || ( $level == 1
                     && $user ne $BoardsById{$1}{'Owner'} ) ) {
                print
                    header,
                    start_html(-style=>{-code=>$CSS},
                               -title=>'drraw - '. $title),
                    $header,
                    h1({-class=>'title'}, a({-href=>&MakeURL}, $title)),
                    h1('Permission Denied!');
            } else {
                unlink "${saved_dir}/d" . $1; # Untaint
                print
                    header,
                    start_html(-style=>{-code=>$CSS}, -title=>'drraw - '.$title,
                               -head=>meta({-http_equiv=>'refresh',
                                            -content=>'1;URL=' .
                                                &MakeURL('Browse',
                                                         'AllDashboards')})),
                    $header,
                    h1({-class=>'title'}, a({-href=>&MakeURL}, $title)),
                    h1('Board Deleted!');
                Indexes_Save('d', $1, '');
            }
        } else {
            print
                header,
                start_html(-style=>{-code=>$CSS}, -title=>'drraw - '. $title),
                $header,
                h1({-class=>'title'}, a({-href=>&MakeURL}, $title)),
                &Error('Deletion failed.');
        }
    } else {
        print
            header,
            start_html(-style=>{-code=>$CSS}, -title=>'drraw - '. $title),
            $header,
            h1({-class=>'title'}, a({-href=>&MakeURL}, $title));
    }

    # New editing session based on an existing graph?
    if ( param('USERSAID') eq 'Edit' ) {
        my ( $user, $board ) = ( param('USERSAID'), param('Dashboard') );
        &Definition_Load('d' . $board);
        param('USERSAID', $user);
        param('Dashboard', $board);
    }

    if ( param('USERSAID') eq 'Save Dashboard'
              || param('USERSAID') eq 'Clone Dashboard' ) {
        if ( param('dTitle') !~ /^(\S| )+$/ ) {
            &Error('Invalid Dashboard Name: ' . param('dTitle'));
        } elsif ( !defined(param('dGrouped')) && param('dCols') < 1 ) {
            &Error('Invalid Number of Columns: ' . param('dCols'));
        } else {
            # Saving/Cloning a definition
            my $dname = time . '.' . $$;
            $dname = param('Dashboard')
                if ( defined(param('Dashboard'))
                     && param('USERSAID') eq 'Save Dashboard' );
            my $msg = 'oink.';
            $msg = param('RCS') if ( defined(param('RCS')) );
            $msg = 'Cloned from '. param('Dashboard')
                if ( param('USERSAID') eq 'Clone Dashboard' );
            if (Definition_Save('d' . $dname, $msg)
                && Indexes_Save('d', $dname, param('dTitle'))) {
                print
                    h1('Dashboard Saved!'),
                    p('The dashboard you just saved may be subsequently edited or cloned, or simply viewed at the following address: ',
                      a({-href=>MakeURL('Mode', 'view', 'Dashboard', $dname)},
                        MakeURL('Mode', 'view', 'Dashboard', $dname))),
                    p('You may continue editing, or ',
                      a({-href=>MakeURL('Browse', 'AllDashboards')},
                        'return to the main page'),
                      '.');
                param(-name=>'Dashboard', -value=>$dname);
            }
        }
        print hr;
    }

    if ( param('USERSAID') ne 'Delete' ) {
        # Finally, edition pages

        # It's all a big form..
        print
            $EditorJS,
            start_form(-method=>'POST', -name=>'Editor'),
            '<input type=hidden name=Type value=DB>';

        print
            '<input type=hidden name=Dashboard value='
            . param('Dashboard') . " />\n"
            if ( defined(param('Dashboard')) );

        if ( param('USERSAID') eq 'Switch' ) {
            if ( defined(param('dGrouped')) ) {
                Delete('dGrouped');
            } else {
                param(-name=>'dGrouped', -value=>'Yes');
            }
        }
        print '<input type=hidden name=dGrouped value=Yes>'
            if ( defined(param('dGrouped')) );

        &BoardOptions;
        
        print
            p({-align=>'center'},
              ( $level == 2
                || ( $level == 1
                     && ( !defined(param('Dashboard'))
                          || ( defined($BoardsById{param('Dashboard')}{'Owner'})
                               && $user eq $BoardsById{param('Dashboard')}{'Owner'} ) ) ) ) ?
              textfield(-name=>'RCS',
                      -default=>'Describe your change(s) here before saving..',
                        -size=>80,
                        -override=>1) .
              ( ( defined(param('Dashboard'))
                && -e $saved_dir .'/RCS/d'. param('Dashboard') .',v' )
              ? a({-href=>MakeURL('Browse', 'Rcs',
                                  'Id', 'd'. param('Dashboard'))},
                  img({-src=>$icon_text, -border=>0}))
              : '' )
              .'<br>'.
              submit(-name=>'USERSAID', -value=>'Save Dashboard')
              : '',
              ( $level > 0 && defined(param('Dashboard')) ) ?
              submit(-name=>'USERSAID', -value=>'Clone Dashboard')
              : ''),
              p({-align=>'center'}, submit(-name=>'USERSAID',
                                           -value=>'Update'));
        
        &BoardConfig;

        print
            end_form;
        
        print hr, &Dump if ( $DEBUG );
    }

    print
        $drrawhome,
        $footer,
        end_html;
} else {
    #
    # Editing pages
    #
    # XXX -- The Type should really be set and checked here...
    if ( $level == 0 ) {
        print
            header(-status=>'Permission Denied'),
            start_html('Permission Denied'),
            $header,
            h2('Permission Denied');
        exit 0;
    }

    &Indexes_Load;

    # Avoids having to check whether it's defined below..
    param('USERSAID', 'New') unless ( defined(param('USERSAID')) );

    # Deleting a saved definition
    if ( param('USERSAID') eq 'Delete' ) {
        if ( defined(param('Graph')) && param('Graph') =~ /^(\d+\.\d+)$/ ) {
            if ($level == 0
                || ( $level == 1
                     && $user ne $GraphsById{$1}{'Owner'} ) ) {
                print
                    header,
                    start_html(-style=>{-code=>$CSS},
                               -title=>'drraw - '. $title),
                    $header,
                    h1({-class=>'title'}, a({-href=>&MakeURL}, $title)),
                    h1('Permission Denied!');
            } else {
                unlink "${saved_dir}/g" . $1; # Untaint
                print
                    header,
                    start_html(-style=>{-code=>$CSS}, -title=>'drraw - '.$title,
                               -head=>meta({-http_equiv=>'refresh',
                                            -content=>'1;URL=' .
                                                &MakeURL('Browse',
                                                         'AllGraphs')})),
                    $header,
                    h1({-class=>'title'}, a({-href=>&MakeURL}, $title)),
                    h1('Graph Deleted!');
                Indexes_Save('g', $1, '');
            }
        } elsif ( defined(param('Template'))
                  && param('Template') =~ /^(\d+\.\d+)$/ ) {
            if ($level == 0
                || ( $level == 1
                     && $user ne $TemplatesById{$1}{'Owner'} ) ){
                print
                    header,
                    start_html(-style=>{-code=>$CSS},
                               -title=>'drraw - '. $title),
                    $header,
                    h1({-class=>'title'}, a({-href=>&MakeURL}, $title)),
                    h1('Permission Denied!');
            } else {
                unlink "${saved_dir}/t" . $1;
                print
                    header,
                    start_html(-style=>{-code=>$CSS}, -title=>'drraw - '.$title,
                               -head=>meta({-http_equiv=>'refresh',
                                            -content=>'1;URL=' .
                                                &MakeURL('Browse',
                                                         'AllTemplates')})),
                    $header,
                    h1({-class=>'title'}, a({-href=>&MakeURL}, $title)),
                    h1('Template Deleted!');
                Indexes_Save('t', $1, '');
            }
        } else {
            print
                header,
                start_html(-style=>{-code=>$CSS}, -title=>'drraw - '. $title),
                $header,
                h1({-class=>'title'}, a({-href=>&MakeURL}, $title)),
            &Error('Deletion failed.');
        }
    } else {
        print
            header,
            start_html(-style=>{-code=>$CSS}, -title=>'drraw - '. $title),
            $header,
            h1({-class=>'title'}, a({-href=>&MakeURL}, $title));
    }

    # New editing session based on an existing graph?
    if ( param('USERSAID') eq 'Edit' ) {
        my ( $user, $graph ) = ( param('USERSAID'), param('Graph') );
        my $template = param('Template');
        &Definition_Load('g' . $graph) if ( defined($graph) );
        &Definition_Load('t' . $template) if ( defined($template) );
        param('USERSAID', $user);
        param('Graph', $graph) if ( defined($graph) );
        param('Template', $template) if ( defined($template) );
    }

    if ( param('USERSAID') eq 'Save Graph'
              || param('USERSAID') eq 'Clone Graph' ) {
        # Saving a graph
        if ( param('gTitle') !~ /^(\S| )+$/ ) {
            &Error('Invalid Graph Name: ' . param('gTitle'));
        } else {
            my $gname = time . '.' . $$;
            $gname = param('Graph')
                if ( defined(param('Graph'))
                     && param('USERSAID') eq 'Save Graph' );
            my $msg = 'oink.';
            $msg = param('RCS') if ( defined(param('RCS')) );
            $msg = 'Cloned from '. param('Graph')
                if ( param('USERSAID') eq 'Clone Graph' );
            &DSLoad;
            if (Definition_Save('g' . $gname, $msg)
                && Indexes_Save('g', $gname, param('gTitle'))) {
                print
                    h1('Graph Saved!'),
                    p('The graph you just saved may be subsequently edited or cloned, or simply viewed at the following address: ',
                      a({-href=>MakeURL('Mode', 'view', 'Graph', $gname)},
                        MakeURL('Mode', 'view', 'Graph', $gname))),
                    p('You may continue editing, or ',
                      a({-href=>MakeURL('Browse', 'AllGraphs')},
                        'return to the main page'),
                      '.');
                param(-name=>'Graph', -value=>$gname);
            }
        }
        Delete('tName'); # May have been cloned from a template
        print hr;
    } elsif ( param('USERSAID') eq 'Save Template'
              || param('USERSAID') eq 'Clone Template' ) {
        # Saving a template
        if ( param('tName') !~ /^(\S| )+$/ ) {
            &Error('Invalid Template Name: ' . param('tName'));
        } elsif ( param('gTitle') !~ /^(\S| )+$/ ) {
            &Error('Invalid Graph Title: ' . param('gTitle'));
        } else {
            my $tname = time . '.' . $$;
            $tname = param('Template')
                if ( defined(param('Template'))
                     && param('USERSAID') eq 'Save Template' );
            my $msg = 'oink.';
            $msg = param('RCS') if ( defined(param('RCS')) );
            $msg = 'Cloned from '. param('Template')
                if ( param('USERSAID') eq 'Clone Template' );
            if (Definition_Save('t' . $tname, $msg)
                && Indexes_Save('t', $tname, param('tName'), param('tRegex'),
                                param('tNiceRegex'))) {
                print
                    h1('Template Saved!'),
                    p('The template you just saved may be subsequently edited or cloned, or simply viewed at the following address: ',
                      a({-href=>MakeURL('Mode', 'view', 'Template', $tname)},
                        MakeURL('Mode', 'view', 'Template', $tname))),
                    p('You may continue editing, or ',
                      a({-href=>MakeURL('Browse', 'AllTemplates')},
                        'return to the main page'),
                      '.');
                param(-name=>'Template', -value=>$tname);
            }
        }
        print hr;
    }

    if ( param('USERSAID') ne 'Delete' ) {
        # General edition page

        # It's all a big form..
        print
            $EditorJS,
            start_form(-method=>'POST', -name=>'Editor');

        if ( param('USERSAID') eq 'Refresh' ) {
            &DBFind;
        } else {
            &Cache_Load;
        }

        &DSLoad unless ( param('USERSAID') eq 'Save Graph'
                         || param('USERSAID') eq 'Clone Graph' );
        &Sort_Colors_Init;

        my $old = scalar(keys(%DS));

        &DBAdd if ( param('USERSAID') eq 'Add DB(s) to data sources' );
        &EVTAdd if ( param('USERSAID') eq 'Add to data sources' );
        if ( scalar(keys(%DS)) > 0 ) {
            param('Code', 'Show') if ( !defined(param('Code')) );
            if ( param('USERSAID') !~ /^Add .*to data sources$/ ) {
                my $gname = time . "." . $$;
                if ( &DRAW(0) ) {
                    Definition_Save($gname, 'Temporary file');
                    print
                        table({-align=>'center', -cellpadding=>5, -border=>1},
                              Tr(td({-align=>'center'},
                                    b('rrdtool&nbsp;invocation'), br,
                                    radio_group(-name=>'Code',
                                                -values=>['Show', 'Hide'],
                                                -default=>param('Code'),
                                                -onclick=>'ShowHideChange(this, "CODE")')))),
                        p({-align=>'center'},
                          &GraphHTML($gname, undef,
                                     param('Start'), param('End')));
                } else {
                    print
                        table({-align=>'center', -cellpadding=>5, -border=>1},
                              Tr(td({-align=>'center'},
                                    b('rrdtool&nbsp;invocation'), br,
                                    radio_group(-name=>'Code',
                                                -values=>['Show', 'Hide'],
                                                -default=>param('Code'),
                                                -onclick=>'ShowHideChange(this, "CODE")'))));
                }
                print
                    p({-align=>'center'},
                      'Start: ', textfield(-name=>'Start'),
                      'End: ', textfield(-name=>'End'),
                      submit(-name=>'USERSAID', -value=>'Update'));
            } elsif ( $old == 0 ) {
                print p(table({-width=>'70%', -align=>'center'},
                              Tr(td({-class=>'help'},
                                    'From here on, you may do the following:',
                                    ul(li('Add more data sources to the graph'),
                                       li('Define graph options'),
                                       li('Configure data sources'),
                                       li('Create a template based on this graph definition'),
                                       li('Save this graph')),
                                    'Click any "Update" button to update the graph preview after making changes to the graph options and/or data source configuration.'))));
            }

            if ( ( defined(param('tName')) && !defined(param('Graph')) )
                 || param('USERSAID') eq 'Make Template' ) {
                # 'Template' needs to be remembered
                print
                    '<input type=hidden name=Template value='
                    . param('Template') . " />\n"
                    if ( defined(param('Template')) );

                print
                    p({-align=>'center'},
                      submit(-name=>'USERSAID', -value=>'Clone Graph')),
                    p({-align=>'center'},
                      ( $level == 2
                        || ( $level == 1
                             && ( !defined(param('Template'))
                                  || ( defined($TemplatesById{param('Template')}{'Owner'})
                                       && $user eq $TemplatesById{param('Template')}{'Owner'} ) ) ) ) ?
                      textfield(-name=>'RCS',
                      -default=>'Describe your change(s) here before saving..',
                                -size=>80,
                                -override=>1) .
                      ( ( defined(param('Template'))
                          && -e $saved_dir .'/RCS/t'. param('Template') .',v' )
                        ? a({-href=>MakeURL('Browse', 'Rcs',
                                            'Id', 't'. param('Template'))},
                            img({-src=>$icon_text, -border=>0}))
                        : '' )
                      .'<br>'.
                      submit(-name=>'USERSAID', -value=>'Save Template')
                      : '',
                      ( $level > 0 && defined(param('Template')) ) ?
                      submit(-name=>'USERSAID', -value=>'Clone Template') :""),
                      hr;

                &TMPLFind(param('tRegex'), param('tNiceRegex'));
                &TMPLConfig;
                print hr;
            } else {
                # 'Graph' needs to be remembered
                print
                    '<input type=hidden name=Graph value='
                    . param('Graph') . " />\n"
                    if ( defined(param('Graph')) );

                print
                    p({-align=>'center'},
                      ( $level == 2
                        || ( $level == 1
                             && ( !defined(param('Graph'))
                                  || ( defined($GraphsById{param('Graph')}{'Owner'})
                                       && $user eq $GraphsById{param('Graph')}{'Owner'} ) ) ) ) ?
                      textfield(-name=>'RCS',
                -default=>'Describe your change(s) here before saving..',
                                -size=>80,
                                -override=>1) .
                      ( ( defined(param('Graph'))
                          && -e $saved_dir .'/RCS/g'. param('Graph') .',v' )
                        ? a({-href=>MakeURL('Browse', 'Rcs',
                                            'Id', 'g'. param('Graph'))},
                            img({-src=>$icon_text, -border=>0}))
                        : '' )
                      .'<br>'.
                      submit(-name=>'USERSAID', -value=>'Save Graph')
                      : '',
                      ( $level > 0 && defined(param('Graph')) ) ?
                      submit(-name=>'USERSAID', -value=>'Clone Graph') : ""),
                    p({-align=>'center'},
                      ( $level > 0 ) ?
                      submit(-name=>'USERSAID', -value=>'Make Template') : ''),
                      hr;
            }

            if ( $old == 0 ) {
                print p(table({-width=>'70%', -align=>'center'},
                              Tr(td({-class=>'help'}, &help_dsconfig))));
            }
            &DSConfig;
            print hr;
            &GOptions;
            print hr;
        } else {
            print
                p(table({-width=>'70%', -align=>'center'},
                        Tr(td({-class=>'help'}, &help_graphfiles))));
        }
        &DBChooser;
        &DBInfo if ( param('USERSAID') eq 'RRD Info for selected DB' );

        print
            end_form;
    
        print hr, &Dump if ( $DEBUG );
    }

    print
        $drrawhome,
        $footer,
        end_html;
}

exit 0;

#
# Loading and saving state
#

sub Cache_Load
{
    if ( scalar(@rrdfiles) > 0 ) {
        &Error("Cache may only be loaded once..\n");
        return;
    }

    if ( -f "${tmp_dir}/rrdfiles" ) {
        if ( time - (stat("${tmp_dir}/rrdfiles"))[9] < $crefresh ) {
            open CACHE, "< ${tmp_dir}/rrdfiles"
                or die "Could not load saved cache (rrdfiles): $!\n";
            while (<CACHE>) {
                chomp;
                push @rrdfiles, $_;
            }
            close CACHE;
        }
    }

    if ( scalar(@evtfiles) > 0 ) {
        &Error("Cache may only be loaded once..\n");
        return;
    }

    if ( -f "${tmp_dir}/evtfiles" ) {
        if ( time - (stat("${tmp_dir}/evtfiles"))[9] < 3600 ) {
            open CACHE, "< ${tmp_dir}/evtfiles"
                or die "Could not load saved cache (evtfiles): $!\n";
            while (<CACHE>) {
                chomp;
                push @evtfiles, $_;
            }
            close CACHE;
        }
    }

    &DBFind if ( scalar(@rrdfiles) == 0 );
}

sub Cache_Save
{
    if ( !open(CACHE, "> ${tmp_dir}/rrdfiles.$$") ) {
        &Error("Could not save cache (evtfiles): $!\n");
        return;
    }

    my $entry;
    foreach $entry ( @rrdfiles ) {
        print CACHE $entry . "\n";
    }
    close CACHE;
    if ( !rename("${tmp_dir}/rrdfiles.$$", "${tmp_dir}/rrdfiles") ) {
        unlink "${tmp_dir}/rrdfiles.$$";
        &Error("Could not save cache (evtfiles): $!\n");
    }

    if ( !open(CACHE, "> ${tmp_dir}/evtfiles.$$") ) {
        &Error("Could not save cache (evtfiles): $!\n");
        return;
    }

    foreach $entry ( @evtfiles ) {
        print CACHE $entry . "\n";
    }
    close CACHE;
    if ( !rename("${tmp_dir}/evtfiles.$$", "${tmp_dir}/evtfiles") ) {
        unlink "${tmp_dir}/evtfiles.$$";
        &Error("Could not save cache (evtfiles): $!\n");
    }
}

sub Indexes_Load
{
    my $cnt = 0;
    while ( $cnt++ < 3 ) {
        last if (sysopen(LOCK, "${saved_dir}/LCK.index", &O_WRONLY|&O_EXCL|&O_CREAT));
        sleep 5;
    }
    close LOCK;
    # We try, but force the lock if necessary..

    if ( -f "${saved_dir}/index" ) {
        open INDEX, "< ${saved_dir}/index"
            or die "Could not load saved index: $!\n";
        while (<INDEX>) {
            chomp;
            if ( /^g(\d+\.\d+):(.+)$/ ) {
                $GraphsById{$1}{'Name'} = $2 if ( -f "${saved_dir}/g${1}" );
            } elsif ( /^g(\d+\.\d+)=(.*)$/ ) {
                $GraphsById{$1}{'Owner'} = $2 if ( -f "${saved_dir}/g${1}" );
            } elsif ( /^t(\d+\.\d+):(.+)$/ ) {
                $TemplatesById{$1}{'Name'} = $2 if ( -f "${saved_dir}/t${1}" );
            } elsif ( /^tfr(\d+\.\d+):(.*)$/ ) {
                $TemplatesById{$1}{'Filter'} = $2
                    if ( -f "${saved_dir}/t${1}" );
            } elsif ( /^tdr(\d+\.\d+):(.*)$/ ) {
                $TemplatesById{$1}{'Display'} = $2
                    if ( -f "${saved_dir}/t${1}" );
            } elsif ( /^drraw=(\d+)\/(.*)$/ ) {
                ( $drraw_ID, $drraw_reported )  = ( $1, $2 );
            } elsif ( /^t(\d+\.\d+)=(.*)$/ ) {
                $TemplatesById{$1}{'Owner'} = $2
                    if ( -f "${saved_dir}/t${1}" );
            } elsif ( /^d(\d+\.\d+):(.+)$/ ) {
                $BoardsById{$1}{'Name'} = $2 if ( -f "${saved_dir}/d${1}" );
            } elsif ( /^dtn(\d+\.\d+):(\w+):t(\d+\.\d+)$/ ) {
                $BoardsById{$1}{'Filters'}{$2}{'Template'} = $3
                    if ( -f "${saved_dir}/d${1}" );
            } elsif ( /^dfr(\d+\.\d+):(\w+):(.*)$/ ) {
                $BoardsById{$1}{'Filters'}{$2}{'Filter'} = $3
                    if ( -f "${saved_dir}/d${1}" );
            } elsif ( /^ddr(\d+\.\d+):(\w+):(.*)$/ ) {
                $BoardsById{$1}{'Filters'}{$2}{'Display'} = $3
                    if ( -f "${saved_dir}/d${1}" );
            } elsif ( /^d(\d+\.\d+)=(.*)$/ ) {
                $BoardsById{$1}{'Owner'} = $2 if ( -f "${saved_dir}/d${1}" );
            } else {
                &Error("Bad index entry: $_\n");
            }
        }
        close INDEX;
    }

    unlink "${saved_dir}/LCK.index";
}

sub Indexes_Save
{
    croak 'Indexes_Save(type, idx, name)'
        if ( scalar(@_) < 3 || scalar(@_) > 5 );
    my ( $type, $idx, $name, $regex, $niceregex ) = ( @_ );

    &Indexes_Load;

    # XXX Small race condition here..

    my $cnt = 0;
    while ( $cnt++ < 3 ) {
        last if (sysopen(LOCK, "${saved_dir}/LCK.index", &O_WRONLY|&O_EXCL|&O_CREAT));
        sleep 5;
    }
    close LOCK;
    # We try, but force the lock if necessary..

    if ( $type ne 'drraw' && !open(LOG, ">> ${saved_dir}/log") ) {
        &Error("Could not append log entry: $!\n");
        unlink "${saved_dir}/LCK.index";
        return 0;
    }
    if ( $name ne '' ) {
        # Hard to say why, but if these two ifs are combined,
        # Perl (5.6.1) bombs out with an "insecure dependency in open" error.
        if ( !open(INDEX, "> ${saved_dir}/index") ) {
            &Error("Could not save index: $!\n");
            unlink "${saved_dir}/LCK.index";
            close LOG unless ( $type eq 'drraw' );
            return 0;
        }
    }
    if ( $type ne 'drraw' ) {
        print LOG time ."|". $type . $idx ."|";
        print LOG $user;
        print LOG " [$ENV{REMOTE_ADDR}]" if ($user eq 'guest');
        print LOG "|$name\n";
        close LOG;
    }

    if ( $name eq '' ) {
        unlink "${saved_dir}/LCK.index";
        # No name means something was deleted
        return;
    }

    if ( $type eq 'g' ) {
        $GraphsById{$idx}{'Name'} = $name;
        if ( $level == 1 ) {
            $GraphsById{$idx}{'Owner'} = $user;
        } else {
            delete($GraphsById{$idx}{'Owner'});
        }
    }
    foreach $idx ( keys(%GraphsById) ) {
        print INDEX 'g' . $idx . ':' . $GraphsById{$idx}{'Name'} . "\n";
        print INDEX 'g' . $idx . '=' . $GraphsById{$idx}{'Owner'} . "\n"
            if ( defined($GraphsById{$idx}{'Owner'}) );
    }
    if ( $type eq 't' ) {
        $TemplatesById{$idx}{'Name'} = $name;
        $TemplatesById{$idx}{'Filter'} = $regex;
        $TemplatesById{$idx}{'Display'} = $niceregex;
        if ( $level == 1 ) {
            $TemplatesById{$idx}{'Owner'} = $user;
        } else {
            delete($TemplatesById{$idx}{'Owner'});
        }
    }
    foreach $idx ( keys(%TemplatesById) ) {
        print INDEX 't' . $idx . ':' . $TemplatesById{$idx}{'Name'} . "\n";
        print INDEX 'tfr' . $idx . ':' . $TemplatesById{$idx}{'Filter'} . "\n";
        print INDEX 'tdr' . $idx . ':' . $TemplatesById{$idx}{'Display'} ."\n";
        print INDEX 't' . $idx . '=' . $TemplatesById{$idx}{'Owner'} . "\n"
            if ( defined($TemplatesById{$idx}{'Owner'}) );
    }
    if ( $type eq 'd' ) {
        $BoardsById{$idx}{'Name'} = $name;
        delete($BoardsById{$idx}{'Template'});
        if ( !defined(param('dGrouped')) ) {
            my $item;
            foreach $item ( grep(/^[a-z]+_Seq$/, param()) ) {
                $item =~ s/_Seq//;
                if ( param("${item}_type") eq 'Base' ) {
                    next unless ( param("${item}_dname") =~ /^t/ );
                    $BoardsById{$idx}{'Filters'}{$item}{'Template'} = param("${item}_dname");
                    $BoardsById{$idx}{'Filters'}{$item}{'Template'} =~ s/^t//;
                    $BoardsById{$idx}{'Filters'}{$item}{'Filter'} = param("${item}_regex");
                    $BoardsById{$idx}{'Filters'}{$item}{'Display'} = param("${item}_row");
                }
            }
        }
        if ( $level == 1 ) {
            $BoardsById{$idx}{'Owner'} = $user;
        } else {
            delete($BoardsById{$idx}{'Owner'});
        }
    }
    ( $drraw_ID, $drraw_reported ) = ( $idx, $name )
        if ( $type eq 'drraw' );
    print INDEX 'drraw='. $drraw_ID .'/';
    if ( defined($drraw_reported) ) {
        print INDEX $drraw_reported;
    }
    print INDEX "\n";
    foreach $idx ( keys(%BoardsById) ) {
        print INDEX 'd' . $idx . ':' . $BoardsById{$idx}{'Name'} . "\n";
        if ( defined($BoardsById{$idx}{'Filters'}) ) {
            my $item;
            foreach $item ( sort keys(%{$BoardsById{$idx}{'Filters'}}) ) {
                print INDEX "dtn${idx}:${item}:t"
                    . $BoardsById{$idx}{'Filters'}{$item}{'Template'} . "\n";
                print INDEX "dfr${idx}:${item}:"
                    . $BoardsById{$idx}{'Filters'}{$item}{'Filter'} . "\n";
                print INDEX "ddr${idx}:${item}:"
                    . $BoardsById{$idx}{'Filters'}{$item}{'Display'} . "\n";
            }
        }
        print INDEX 'd' . $idx . '=' . $BoardsById{$idx}{'Owner'} . "\n"
            if ( defined($BoardsById{$idx}{'Owner'}) );
    }
    close INDEX;

    unlink "${saved_dir}/LCK.index";
    return 1;
}

my $silver = 0;
sub GraphIndex
{
    croak 'GraphIndex(regex)' if ( scalar(@_) != 1 );

    my $gid;
    foreach $gid ( sort { $GraphsById{$a}{'Name'} cmp $GraphsById{$b}{'Name'} }
                   keys(%GraphsById) ) {
        next unless ( $GraphsById{$gid}{'Name'} =~ /$_[0]/ );
        print
            Tr({-class=>($silver++ % 2 == 0) ? 'header' : ''},
               td(a({-href=>MakeURL('Mode', 'view', 'Graph', $gid)},
                    '[View]'), ' ',
                  ( $level > 0 ) ?
                  ( $level == 2
                    || ( defined($GraphsById{$gid}{'Owner'})
                         && $user eq $GraphsById{$gid}{'Owner'} ) ) ?
                  a({-href=>MakeURL('USERSAID', 'Edit', 'Graph', $gid)},
                    '[Edit/Clone]') .' '.
                  a({-href=>MakeURL('USERSAID', 'Delete', 'Graph',$gid)},
                    '[Delete]') .' '
                  : '[Edit/'.
                  a({-href=>MakeURL('USERSAID', 'Edit', 'Graph', $gid)},
                    'Clone'). '] [Delete] '
                  : '',
                  b($GraphsById{$gid}{'Name'})),
               td(''), td(''));
    }
}

sub TemplateIndex
{
    croak 'TemplateIndex(regex)' if ( scalar(@_) != 1 );

    my $tid;
    foreach $tid ( sort { $TemplatesById{$a}{'Name'}
                          cmp $TemplatesById{$b}{'Name'} }
                   keys(%TemplatesById) ) {
        next unless ( $TemplatesById{$tid}{'Name'} =~ /$_[0]/ );
        &TMPLFind($TemplatesById{$tid}{'Filter'},
                  $TemplatesById{$tid}{'Display'});
        print
            Tr({-class=>($silver++ % 2 == 0) ? 'header' : ''},
               td(a({-href=>MakeURL('Mode', 'view', 'Template', $tid)},
                    '[View]'), ' ',
                  ( $level > 0 ) ?
                  ( $level == 2
                    || ( defined($TemplatesById{$tid}{'Owner'})
                         && $user eq $TemplatesById{$tid}{'Owner'} ) ) ?
                  a({-href=>MakeURL('USERSAID', 'Edit', 'Template', $tid)},
                    '[Edit/Clone]') .' '.
                  a({-href=>MakeURL('USERSAID', 'Delete',
                                    'Template', $tid)},
                    '[Delete]')
                  : '[Edit/'.
                  a({-href=>MakeURL('USERSAID', 'Edit', 'Template', $tid)},
                    'Clone') .'] [Delete] '
                  : '', ' ',
                  b($TemplatesById{$tid}{'Name'})),
               td({-align=>'right'}, ' (' . scalar(keys(%TMPL)) . ')'),
               td(start_form(-method=>'GET'),
                  submit(-name=>'Mode', -value=>'view'),
                  '<input type=hidden name=Template value='. $tid ." />\n",
                  ($IndexMax <= 0 || scalar(keys(%TMPL)) <= $IndexMax) ?
                  popup_menu(-name=>'Base',
                             -values=>[sort { $TMPL{$a} cmp $TMPL{$b} }
                                       keys(%TMPL)],
                             -labels=>\%TMPL) : '',
                  end_form));
    }
}

sub DashBoardIndex
{
    croak 'DashBoardIndex(regex)' if ( scalar(@_) != 1 );

    my $did;
    foreach $did ( sort { $BoardsById{$a}{'Name'} cmp $BoardsById{$b}{'Name'} }
                   keys(%BoardsById) ) {
        next unless ( $BoardsById{$did}{'Name'} =~ /$_[0]/ );
        &BoardFind($did);
        my @extra;
        if ( defined($BoardsById{$did}{'Filters'}) ) {
            $extra[0] = ' (' . scalar(keys(%DBTMPL)) . ')';
            $extra[1] =
                start_form(-method=>'GET') .
                submit(-name=>'Mode', -value=>'view') .
                '<input type=hidden name=Dashboard value='. $did ." />\n";
            $extra[1] .= popup_menu(-name=>'Base',
                                    -values=>[sort keys(%DBTMPL)])
                if ($IndexMax <= 0 || scalar(keys(%DBTMPL)) <= $IndexMax);
            $extra[1] .= end_form;
        } else {
            $extra[0] = '';
            $extra[1] =
                start_form(-method=>'GET') .
                submit(-name=>'Mode', -value=>'view') .
                ' with filter: ' .
                '<input type=hidden name=Dashboard value='. $did ." />" .
                textfield(-name=>'Filter', -default=>'') .
                end_form;
        }
        print
            Tr({-class=>($silver++ % 2 == 0) ? 'header' : ''},
               td(a({-href=>MakeURL('Mode', 'view', 'Dashboard', $did)},
                    '[View]'), ' ',
                  ( $level > 0 ) ?
                  ( $level == 2
                    || ( defined($BoardsById{$did}{'Owner'})
                         && $user eq $BoardsById{$did}{'Owner'} ) ) ?
                  a({-href=>MakeURL('USERSAID', 'Edit', 'Type', 'DB',
                                    'Dashboard', $did)},
                    '[Edit/Clone]') .' '.
                  a({-href=>MakeURL('USERSAID', 'Delete', 'Type', 'DB',
                                    'Dashboard', $did)},
                    '[Delete]')
                  : '[Edit/'.
                  a({-href=>MakeURL('USERSAID', 'Edit', 'Type', 'DB',
                                    'Dashboard', $did)}, 'Clone')
                  .'] [Delete] '
                  : '', ' ',
                  b($BoardsById{$did}{'Name'}),
                  td({-align=>'right'}, $extra[0]),
                  td($extra[1])));
    }
}

sub CustomIndex
{
    croak 'CustomIndex(ref, id)' if ( scalar(@_) != 2);
    my ( $ref, $id ) = ( @_ );

    die "Please update CGI.pm to version 3.05 or newer to use Custom Indexes\n"
        if ( $CGI::VERSION > 2.91 && $CGI::VERSION < 3.05 );

    if ( ref($ref) eq 'HASH' ) {
        my $key;
        foreach $key ( sort keys %{$ref} ) {
            my $name = $key;
            $name = $1 if ( $key =~ /^\d+\s+(\S.*)$/ );
            my $nid = $id . $name ."\n";
            my $uniqid = $$ref{$key};
            if ( ref($$ref{$key}) eq 'HASH' ) {
                my $icon = $icon_closed;
                my $style = 'style="display:none"';
                if ( defined(param('Browse'))
                     && $nid eq substr(param('Browse'), 0, length($nid)) ) {
                    $icon = $icon_open;
                    $style = '';
                }
                print
                    "<div id=\"$uniqid\" style=\"cursor:pointer\" onclick=\"IndexClick(this)\">",
                    img({-src=>$icon, -border=>0, -id=>"${uniqid}-icon"}),
                    ' '. $name,
                    '<NOSCRIPT>',
                    ' [Access to this menu requires JavaScript to be enabled]',
                    '</NOSCRIPT>',
                    br,
                    '</div>',
                    "<div id=\"${uniqid}-child\" $style>",
                    '<blockquote class=padless>';
                &CustomIndex($$ref{$key}, $nid);
                print '</blockquote></div>';
            } else {
                if ( defined(param('Browse')) && $nid eq param('Browse') ) {
                    # User selected index item, list it
                    print
                        "<div id=\"$uniqid\" style=\"cursor:pointer\" onclick=\"IndexClick(this)\">",
                        img({-src=>$icon_open, -border=>0,
                             -id=>"${uniqid}-icon"}),
                        ' '. $name, br,
                        '</div>',
                        "<div id=\"${uniqid}-child\">",
                        '<blockquote class=padless>',
                        start_table{-width=>'100%'};
                    &Cache_Load;
                    if ( ref($$ref{$key}) eq 'ARRAY' ) {
                        &GraphIndex($$ref{$key}[0])
                            if ( defined($$ref{$key}[0]) );
                        &TemplateIndex($$ref{$key}[1])
                            if ( defined($$ref{$key}[1]) );
                        &DashBoardIndex($$ref{$key}[2])
                            if ( defined($$ref{$key}[2]) );
                    } elsif ( ! ref($$ref{$key}) ) {
                        &GraphIndex($$ref{$key});
                        &TemplateIndex($$ref{$key});
                        &DashBoardIndex($$ref{$key});
                    } else {
                        die 'Bad %Index entry ['. $key .']: '. ref($$ref{$key}) ."\n";
                    }
                    print
                        end_table,
                        '</blockquote></div>';
                } else {
                    # Not an open index item, just provide a link
                    print
                        a({-href=>MakeURL('Browse', $nid)},
                          img({-src=>$icon_closed, -border=>0}),
                          ' '. $name), br;
                }
            }
        }
    } else {
        die 'Bad %Index entry: '. ref($ref) ."\n";
    }
}

# Loading and Saving a Graph (or Template) definition

sub Definition_Load
{
    croak 'Definition_Load(file)' if ( scalar(@_) != 1);
    my ( $file ) = ( @_ );

    die "Invalid Request: $file\n" unless ( $file =~ /^(g|t|d)*\d+\.\d+$/ );

    if ( $file =~ /^(g|t|d)/ ) {
        $file = $saved_dir . '/' . $file;
    } else {
        $file = $tmp_dir . '/' . $file;
    }

    open(FILE, "< ${file}")
        or die "Could not open definition file \"$file\": $!\n";;
    restore_parameters(\*FILE);
    close FILE;

    my @plist = param();
    while ( scalar(@plist) > 0) {
        Delete($plist[0]) if ( $plist[0] ne 'DS'
                               && $plist[0] !~ /^[a-z]+_/
                               && $plist[0] !~ /^(g|t|d)/ ); # XXX Too vague..
        shift @plist;
    }

    my $gform = param('gFormat');
    param('gFormat', 'PNG')
        if ( defined($gform) && $gform eq 'GIF' && $RRDs::VERSION >= 1.2 );

    param('Code', 'Hide');
}

sub Definition_Get
{
    croak 'Definition_Get(file, name)' if ( scalar(@_) != 2);
    my ( $file, $name ) = ( @_ );

    die "Invalid Request: $file\n" unless ( $file =~ /^(g|t|d)*\d+\.\d+$/ );

    if ( $file =~ /^(g|t|d)/ ) {
        $file = $saved_dir . '/' . $file;
    } else {
        $file = $tmp_dir . '/' . $file;
    }

    if (!open(FILE, "< ${file}")) {
        &Error("Could not open definition file \"$file\" to get $name: $!");
        return undef;
    }
    my $def = new CGI(\*FILE);
    close FILE;

    return $def->param($name);
}

sub Definition_Save
{
    croak 'Definition_Save(file, msg)' if ( scalar(@_) != 2);
    my ( $file, $log ) = ( @_ );

    my $rcsuser;
    if ( $user ne 'guest' ) {
        $rcsuser = '-w'. $user;
    } else {
        $rcsuser = "-wguest[$ENV{REMOTE_ADDR}]";
    }
    $rcsuser =~ /(.+)/; $rcsuser = $1;  # Untaint

    my $rcs;

    if ( $file =~ /^([dgt])(\d+\.\d+)/ ) {
        $file = $saved_dir . '/' . $1 . $2; # Untaint
        my $owner;
        $owner = $GraphsById{$2}{'Owner'} if ( $1 eq 'g' );
        $owner = $TemplatesById{$2}{'Owner'} if ( $1 eq 't' );
        $owner = $BoardsById{$2}{'Owner'} if ( $1 eq 'd' );
        if ( $level == 0
             || ( $level == 1 && -f "${file}"
                  && ( !defined($owner) || $owner ne $user ) ) ){
            print h1('Permission Denied');
            return 0;
        }
        if ( $use_rcs ) {
            $rcs = new Rcs;
            $rcs->file($1 . $2);
            $rcs->workdir($saved_dir);
            $rcs->rcsdir($saved_dir .'/RCS');
            if ( -f $saved_dir .'/RCS/'. $1 . $2 .',v' ) {
                if ( $rcs->co('-l') != 1 ) {
                    &Error("Failed to check out $file");
                    return 0;
                }
            }
        }
    } else {
        $file = $tmp_dir . '/' . $file;
    }

    my $ok = 0;
    if ( open(FILE, "> ${file}") ) {
        save_parameters(\*FILE);
        close FILE;
        $ok = 1;
    } else {
        &Error("Could not save definition: $!");
    }

    if ( $use_rcs && defined($rcs) ) {
        if ( $ok && $log =~ /(.+)/ ) {
            if ( $rcs->ci('-u', '-m'. $1, $rcsuser) != 1 ) {
                &Error("Failed to check in $file");
                $ok = 0;
            }
        } else {
            if ( $rcs->co('-u', '-f') != 1 ) {
                &Error("Failed to unlock $file");
            }
        }
    }
    return $ok;
}

# Finding RRD files

sub DBFind
{
    @rrdfiles = ();
    @evtfiles = ();
    alarm(0); # This may take a while, and that'd be okay..
    find({wanted=>\&DBFinder, no_chdir=>1, follow=>1,
          untaint=>1, # Untaint, lame...
          untaint_pattern=>qr|^([-+@\w./:]+)$|}, keys(%datadirs));
    alarm($maxtime);
    Cache_Save;
}

sub DBFinder
{
    if ( -f $_ && ( /.\.rrd$/ || /.\.evt$/ ) ) {
        my $start;
        foreach $start ( keys(%datadirs) ) {
            if ( $_ =~ /^${start}\/(.+)$/ ) {
                my $end = $1;
                if ( $_ =~ /\.rrd$/ ) {
                    push @rrdfiles, $start . '//' . $end;
                } else {
                    push @evtfiles, $start . '//' . $end;
                }
                return;
            }
        }
        warn "DBFinder called for $_ which does not match any of \%datadirs: ". join(", ", keys(%datadirs)) ."\n";
        die "Something is wrong in DBFinder... (". $File::Find::dir .")\n";
    }
}

# Lists available RRD files for the user to pick
sub DBChooser
{
    my ( %rrd, %evt );
    my $file;
    foreach $file ( sort(@rrdfiles) ) {
        if ( $file =~ /^(.+)\/\/(.+)\.rrd$/ ) {
            $rrd{$file} = $datadirs{$1} . " " . $2;
        } else {
            die "Bad (rrd) filename in DBChooser: $file\n";
        }
    }
    foreach $file ( sort(@evtfiles) ) {
        if ( $file =~ /^(.+)\/\/(.+)\.evt$/ ) {
            $evt{$file} = $datadirs{$1} . " " . $2;
        } else {
            die "Bad (evt) filename in DBChooser: $file\n";
        }
    }

    print
        p({-align=>'center'},
          b('Filename filter regexp: '),
          textfield(-name=>'fn_FILTER', -default=>'', -size=>40),
          submit(-name=>'USERSAID', -value=>'Filter'));

    my @files;
    @files = sort { &datafnsort($rrd{$a}, $rrd{$b}) } (keys(%rrd));
    my $filter = param('fn_FILTER');
    @files = grep { $rrd{$_} =~ /$filter/ } @files
        if ( defined(param('fn_FILTER')) && param('fn_FILTER') ne '' );
    
    print
        p({-align=>'center'},
          b(em('Available Database Files')),
          br,
          submit(-name=>'USERSAID', -value=>'Refresh'),
          br,
          scrolling_list(-name=>'db_FILES',
                         -multiple=>'true',
                         -size=>10,
                         -values=>\@files,
                         -labels=>\%rrd,
                         -default=>[])),
        p({-align=>'center'},
          b('Data Source filter regexp: '),
          textfield(-name=>'db_FILTER', -default=>$dsfilter_def, -size=>40),
          br,
          b('Data Source RRA(s): '),
          checkbox_group(-name=>'db_RRAS',
                         -values=>[@rranames],
                         -label=>\%rranames),
          br,
          submit(-name=>'USERSAID', -value=>'Add DB(s) to data sources'),
          br,
          submit(-name=>'USERSAID', -value=>'RRD Info for selected DB'));

    @files = sort { &datafnsort($evt{$a}, $evt{$b}); } (keys(%evt));
    @files = grep { $evt{$_} =~ /$filter/ } @files
        if ( defined(param('fn_FILTER')) && param('fn_FILTER') ne '' );

    print
        p({-align=>'center'},
          b(em('Available Event Files')),
          br,
          submit(-name=>'USERSAID', -value=>'Refresh'),
          br,
          scrolling_list(-name=>'evt_FILES',
                         -multiple=>'true',
                         -size=>10,
                         -values=>\@files,
                         -labels=>\%evt,
                         -default=>[])),
        p({-align=>'center'},
          submit(-name=>'USERSAID', -value=>'Add to data sources'));

}

# DS Addition: Validation and Initialization
sub DBAdd
{
    my ( $cf, $file );

    if ( !defined(param('db_RRAS')) ) {
        &Error('You must specify at least one RRA to add!');
        return;
    }

    my @dbs = param('db_FILES');
    if ( scalar(@dbs) == 0 ) {
        &Error('You must select at least one DB file to add data source(s) from!');
        return;
    }

    foreach $cf ( param('db_RRAS') ) {
        foreach $file ( param('db_FILES') ) {
            my ($last) = RRDs::last($file);
            my ($start, $step, $DSnames, $data) = RRDs::fetch($file, $cf);
            my $ds;
            if ( defined($DSnames) && scalar(@{$DSnames}) > 0 ) {
                foreach $ds ( @{$DSnames} ) {
                    my $filter = param('db_FILTER');
                    next if ( $ds !~ /$filter/ );
                    &DSNew($file, $ds, $cf);
                }
            } else {
                &Error("No data source for \"${cf}\" RRA found in file \"${file}\"!");
            }
        }
    }
}

# DS Info: Validation and Initialization
sub DBInfo
{
    my ( $cf, $file );

    my @dbs = param('db_FILES');
    if ( scalar(@dbs) == 0 ) {
        &Error('You must select at least one DB file to get info from!');
        return;
    }

    foreach $file ( param('db_FILES') ) {
        my $hash = RRDs::info($file);
        if (RRDs::error) {
            &Error("Error getting info for \"${file}\": ". RRDs::error);
        } else {
            print
                p({-align=>'center'},
                  b(em('Info for Database File ' . $file)));
            my $first = '';
            foreach my $key (sort keys %$hash) {
                print "$key = $$hash{$key}", br
                    unless ( $key =~ /^(ds|rra)\[/ );
            }
            print
                start_table({-align=>'center', -border=>1}),
                '<tr><th>DS</th>';
            foreach my $key (sort keys %$hash) {
                if ( $key =~ /^ds\[.+\]\.(.+)$/ ) {
                    last if ( $first eq $1 );
                    $first = $1 if ( $first eq '' );
                    print th($1);
                }
            }
            $first = '';
            foreach my $key (sort keys %$hash) {
                if ( $key =~ /^ds\[(.+)\]\..+$/ ) {
                    if ( $first ne $1 ) {
                        $first = $1;
                        print '</tr><tr>', td($first);
                    }
                    print td($$hash{$key});
                }
            }
            print
                '</tr>',
                end_table,
                start_table({-align=>'center', -border=>1}),
                '<tr><th>rra</th>';
            $first = '';
            foreach my $key (sort keys %$hash) {
                if ( $key =~ /^rra\[[^[]+\]\.([^[]+)$/ ) {
                    last if ( $first eq $1 );
                    $first = $1 if ( $first eq '' );
                    print th($1);
                }
            }
            $first = '';
            foreach my $key (sort keys %$hash) {
                if ( $key =~ /^rra\[([^[]+)\]\.[^\[]+$/ ) {
                    if ( $first ne $1 ) {
                        $first = $1;
                        print '</tr><tr>', td($first);
                    }
                    print td($$hash{$key});
                }
            }
            print
                '</tr>',
                end_table,
                start_table({-align=>'center', -border=>1}),
                '<tr><th>rra</th><th>ds</th>';
            $first = '';
            foreach my $key (sort keys %$hash) {
                if ( $key =~ /^rra\[.+\]\.cdp_prep\[.+\]\.(.+)$/ ) {
                    last if ( $first eq $1 );
                    $first = $1 if ( $first eq '' );
                    print th($1);
                }
            }
            $first = '';
            foreach my $key (sort keys %$hash) {
                if ( $key =~ /^rra\[(.+)\]\.cdp_prep\[(.+)\]\..+$/ ) {
                    if ( $first ne "$1.$2" ) {
                        $first = "$1.$2";
                        print '</tr><tr>', td($1), td($2);
                    }
                    print td($$hash{$key});
                }
            }
            print '</tr>';
            print end_table;
        }
    }
}

sub EVTAdd
{
    my ( $cf, $file );

    my @evts = param('evt_FILES');
    if ( scalar(@evts) == 0 ) {
        &Error("You must select at least one event file to add!");
        return;
    }

    foreach $file ( param('evt_FILES') ) {
        my $new = &DSNew($file, '', '');
        param(-name=>"${new}_Label", -value=>'');
    }
}

# DS Load:
sub DSLoad
{
    my $seq = 1;
    my $ds;
    foreach $ds ( sort {
                       my $param_a =  param("${a}_Seq");
                       my $param_b = param("${b}_Seq");
                       if (defined($param_a)) {
                           if (defined($param_b)) {
                               return ($param_a <=> $param_b);
                           } else {
                               return -1;
                           }
                       } else {
                           if (defined($param_b)) {
                               return 1;
                           } else {
                               return 0;
                           }
                       }
                  }
                  param('DS') ) {
        if ( defined($DS{$ds}) ) {
            &Error("Discarded duplicate DS definition: $ds");
            next;
        }
        if ( defined(param("${ds}_DELETE")) ) {
            foreach ( grep(/^${ds}_/, param()) ) {
                Delete($_);
            }
            next;
        }
        # Automatically deduplicate Sequence numbers to be more friendly
        param("${ds}_Seq", $seq++);
        if ( defined(param("${ds}_NEW"))
             && param("${ds}_File") eq '' && param("${ds}_DS") eq '' 
             && param("${ds}_Element") eq '' && param("${ds}_Formula") eq '' ){
            Delete("${ds}_Formula");
        }
        if ( defined(param("${ds}_Formula")) ) {
            my $fre = param("${ds}_File");
            $fre =~ s!/+!/!g;
            param(-name=>"${ds}_File", -value=>$fre);
            $DS{$ds} = param("${ds}_File");
            param(-name=>"${ds}_Type", -value=>'-Nothing-')
                if ( param("${ds}_Formula") eq "" );
        } elsif ( param("${ds}_File") ne '' ) {
            if ( param("${ds}_File") =~ /^(.+)\/\/(.+).(rrd|evt)$/ ) {
                $DS{$ds} = $datadirs{$1} . " " . $2;
            } else {
                &Error("Invalid filename for DS \"$ds\": "
                       . param("${ds}_File"));
                next;
            }
        } else {
            $DS{$ds} = '';
            &Error("CDEF ${ds} configured but NOT defined!")
                if ( param("${ds}_CDEF") eq '' );
        }
        param(-name=>"${ds}_Color", -value=>'White')
            if ( param("${ds}_Type") =~ /(PRINT|COMMENT|-Nothing-)/ );
        param(-name=>"${ds}_Width", -value=>'')
            unless ( defined(param("${ds}_Width")) );
        param(-name=>"${ds}_tWidth", -value=>'')
            if ( defined(param("${ds}_Formula"))
                 && !defined(param("${ds}_tWidth")) );
    }
    param(-name=>'DS', -value=>[keys(%DS)]);
}

# DS Config: Lists chosen DS for the user to configure
sub DSConfig
{
    my $color;
    my $i =0;
    print
        '<p>',
        start_table({-border=>1,-align=>'center'}),
        caption(em(b('Available Colors'))), '<tr>';
    foreach $color ( sort Sort_Colors (keys(%colors)) ) {
        next if ( $colors{$color} eq '' );
        print "<td bgcolor=" . $colors{$color} . '>';
        if ( (77*hex(substr($colors{$color}, 1, 2))
              + 137*hex(substr($colors{$color}, 3, 2))
              + 29*hex(substr($colors{$color}, 5, 2))) / 256 < 128 ) {
            print '<font color=white>' . b($color) . '</font>';
        } else {
            print b($color);
        }
        print '</td>';
        $i++;
        print "</tr>\n<tr>" if (($i % 6) == 0);
    }
    print
        '</tr>' . end_table,
        '<script type="text/javascript">var colors = [ ';
    foreach $color ( keys(%colors) ) {
        print "\"${color}\", \"$colors{$color}\", ";
    }
    print ']</script>';

    my @new = ( &DSNew('', '', ''), &DSNew('', '', ''), &DSNew('', '', '') );
    param("$new[0]_DELETE", 'Y');
    param("$new[1]_DELETE", 'Y');
    param("$new[2]_DELETE", 'Y');

    my ( %rrd, %evt );
    my $file;
    foreach $file ( sort(@rrdfiles) ) {
        if ( $file =~ /^(.+)\/\/(.+)\.rrd$/ ) {
            $rrd{$file} = $datadirs{$1} . " " . $2;
        }
    }
    foreach $file ( sort(@evtfiles) ) {
        if ( $file =~ /^(.+)\/\/(.+)\.evt$/ ) {
            $evt{$file} = $datadirs{$1} . " " . $2;
        }
    }

    my %rra = map{ $_ => '' } @rranames;
    
    my $ds;
    foreach $ds ( keys(%DS) ) {
        print
            "<input type=hidden name=DS value=$ds />\n";
    }

    print
        p({-align=>'center'}, submit(-name=>'USERSAID',
                                     -value=>'Update')),
        start_table({-border=>1,-width=>'100%'}),
        caption(em(b('Data Source Configuration'),
                   a({-href=>MakeURL('Browse', 'Help') . '#dsconfig',
                      -target=>'drrawhelp'}, small('Help')))),
        Tr({-class=>'header'}, th(['DEL'. br .'?',
                                     'Name'. br .'Seq',
                                     'Data Source ('.
                                     checkbox(-name=>'DSFList',
                                              -title=>'Select to enable drop-down filename lists',
                                              -label=>'Lists', -value=>'Y')
                                     .')',
                                     'RRA'. br .'CDEF',
                                     'Type'. br .'Color', 'Label / Format',
                                     'Additional GPRINTs'. br .
                                     small({-style=>'cursor:pointer'},
                                           join('/', map { span({-onclick=>"ToggleXTRA(\"$_\")"}, $rranames{$_}) } @rranames)). br .
                                     small(radio_group(-name=>'XTRAS',
                                                       -title=>'Click above to toggle all additional GPRINTs and BR checkboxes',
                                                       -values=>['On',
                                                                 'Off'])),
                                     small({-style=>'cursor:pointer',
                                            -onclick=>'ToggleXTRA("BR")'},
                                           'BR')]));

    foreach $ds ( sort { param("${a}_Seq") <=> param("${b}_Seq") }
                  keys(%DS) ) {
        Delete("${ds}_DELETE") unless ( grep(/^$ds$/, @new) );
        # Extra rows for "new" DS entries
        my $n;
        if ( $ds eq $new[0] ) {
            $n = 0;
            print
                Tr({-class=>'header'}, td({-colspan=>8, -align=>'center', -id=>'new0', -onclick=>'ShowNew(this, '. $n .')'}, 'Use the following row to define a new CDEF or VDEF.'));
        } elsif ( $ds eq $new[1] ) {
            $n = 1;
            print
                Tr({-class=>'header'}, td({-colspan=>8, -align=>'center', -id=>'new1', -onclick=>'ShowNew(this, '. $n .')'}, 'Use the following row to add a new RULE to the graph.'));
        } elsif ( $ds eq $new[2] ) {
            $n = 2;
            print
                Tr({-class=>'header'}, td({-colspan=>8, -align=>'center', -id=>'new2', -onclick=>'ShowNew(this, '. $n .')'}, 'Use the following row to define a DS based on a perl regular expression.'));
        }

        # Figure out once and for all what we're dealing with
        my $type;
        my $rowcount = 1;
        if ( defined(param("${ds}_Formula")) || $ds eq $new[2] ) {
            # DS Template
            $type = 'tmpl';
            $rowcount = 2 unless ( $ds eq $new[2] );
        } elsif ( param("${ds}_File") eq '' ) {
            if ( param("${ds}_Type") !~ /RULE$/
                 && !&IsNum(param("${ds}_CDEF"))
                 && $ds ne $new[1]) {
                # Pure CDEF
                $type = 'def';
            } else {
                # RULE
                $type = 'rule';
            }
        } elsif ( param("${ds}_File") =~ /\.rrd$/ ) {
            # Regular DS
            $type = 'ds';
        } elsif ( param("${ds}_File") =~ /\.evt$/ ) {
            # Event file
            $type = 'evt';
        } else {
            print Tr(td({-rowspan=>8, -class=>'error', -align=>'center'},
                        "Skipping invalid DS $ds"));
            next;
        }

        print "<tr class='small'";
        print " id='newrow". $n ."' " if ( defined($n) );
        print ">";
        # Delete box
        print td({-align=>'center', -rowspan=>$rowcount},
                 checkbox(-name=>"${ds}_DELETE",
                          -title=>'If checked, this row will be deleted on the next update',
                          -label=>'', -value=>'Y',
                          -onclick=>"DisableToggle(\"${ds}\")"));

        # Name/Seq
        print td(b($ds) . ':' . textfield(-name=>"${ds}_Seq",
                                          -title=>'Change to reorder the table rows',
                                          -class=>'small',
                                          -default=>param("${ds}_Seq"),
                                          -size=>2));

        # Data Source.. ouch
        if ( $type eq 'ds' ) {
            print td(( defined(param('DSFList')) && param('DSFList') eq 'Y' )?
                     popup_menu(-name=>"${ds}_File", -class=>'small',
                                -values=>[sort { &datafnsort($rrd{$a},
                                                             $rrd{$b}) }
                                          keys(%rrd)],
                                -labels=>\%rrd,
                                -default=>$DS{$ds})
                     : (hidden("${ds}_File") . $DS{$ds}),
                     br,
                     b(param("${ds}_DS")),
                     hidden("${ds}_DS", param("${ds}_DS")));
        } elsif ( $type eq 'evt' ) {
            print td(( defined(param('DSFList')) && param('DSFList') eq 'Y' )?
                     popup_menu(-name=>"${ds}_File", -class=>'small',
                                -values=>[sort { &datafnsort($evt{$a},
                                                             $evt{$b}) }
                                          keys(%evt)],
                                -labels=>\%evt,
                                -default=>$DS{$ds})
                     : (hidden("${ds}_File") . $DS{$ds}),
                     br, 'Event Filter Regex: ',
                     textfield(-name=>"${ds}_EvtFilter", -class=>'small',
                               -default=>param("${ds}_EvtFilter"))),
                     td({-align=>'center'}, 'N/A');
        } elsif ( $type eq 'tmpl' ) {
            print td('File&nbsp;RE:',
                     textfield(-name=>"${ds}_File", -class=>'small',
                               -title=>'All files matching the regular expression will automatically be generated Data Sources on the graph',
                               -default=>param("${ds}_File"), -size=>40), br,
                     'DS:', textfield(-name=>"${ds}_DS", -class=>'small',
                                      -title=>'Name of the Data Source to use',
                                      -default=>param("${ds}_DS")), br,
                     'Element:',
                     textfield(-name=>"${ds}_Element", -class=>'small',
                               -title=>'Use this to define a CDEF for each individual Data Source',
                               -default=>param("${ds}_Element")),
                     'Formula:',
                     textfield(-name=>"${ds}_Formula", -class=>'small',
                               -title=>'Operation to use to combine all Data Sources into one, for example, for the total, use "+"',
                               -default=>param("${ds}_Formula")),
                     hidden("${ds}_NEW", ''));
        } elsif ( $type eq 'def' ) {
            my $v = '';
            $v = '/VDEF' if ( $RRDs::VERSION >= 1.2 );
            print td({-align=>'center'}, "CDEF${v} Definition",
                     hidden("${ds}_File", ''));
        } elsif ( $type eq 'rule' ) {
            print td({-align=>'center'}, 'RULE Definition',
                     hidden("${ds}_File", ''));
        } else {
            die "Ugh, we should not be here!";
        }

        # RRA/CDEF
        my @rra = @rranames;
        if ( $RRDs::VERSION >= 1.2 && $type eq 'def' ) {
            if ( param("${ds}_Type") ne 'GPRINT'
                 || param("${ds}_RRA") =~ /^.DEF$/ ) {
                @rra = ();
                param("${ds}_RRA", 'CDEF')
                    unless ( param("${ds}_RRA") =~ /^.DEF$/ );
            }
            push @rra, 'VDEF', 'CDEF';
        }            
        print td(( $type eq 'rule' ) ? 'Value:'
                 : popup_menu(-name=>"${ds}_RRA", -class=>'small',
                              -values=>[@rra],
                              -labels=>\%rranames,
                              -default=>param("${ds}_RRA")),
                 br,
                 textfield(-name=>"${ds}_CDEF", -class=>'small',
                           -title=>'CDEF/VDEF formula',
                           -default=>param("${ds}_CDEF"),
                           -size=>11))
            unless ( $type eq 'evt' );

        # Type/Color
        my @gt;
        if ( $type eq 'evt' ) {
            @gt = ( 'VRULE' );
        } elsif ( $type eq 'rule' ) {
            # HRULE deprecated in 1.2
            if ( $RRDs::VERSION >= 1.2 ) {
                if ( param("${ds}_Type") eq 'VRULE' ) {
                    @gt = ( 'VRULE' );
                } else {
                    @gt = grep(/(RULE$|^LINE)/, @graphtypes);
                }
            } else {
                @gt = grep(/RULE$/, @graphtypes);
            }
        } elsif ( $RRDs::VERSION >= 1.2 ) {
            # HRULE & STACK deprecated in 1.2
            if ( param("${ds}_Type") ne 'STACK' ) {
                @gt = grep(!/^(HRULE|STACK)$/, @graphtypes);
            } else {
                @gt = grep(!/^HRULE$/, @graphtypes);
            }
        } else {
            @gt = grep(!/^(LINE\?|SHIFT|TICK)$/, @graphtypes);
        }

        print td({-bgcolor=>$colors{param("${ds}_Color")},
                  -id=>"${ds}_Colorize"},
                 popup_menu(-name=>"${ds}_Type", -class=>'small',
                            -id=>"${ds}_Type",
                            -values=>[@gt],
                            -default=>param("${ds}_Type"),
                            -onchange=>"TypeCB(this.value, \"${ds}\")"),
                 ( $RRDs::VERSION >= 1.2
                   && ( $type eq 'ds' || $type eq 'def' || $type eq 'tmpl' )) ?
                 checkbox(-id=>"${ds}_STACK", -name=>"${ds}_STACK",
                          -title=>'Whether to stack this element on top of the previous one',
                          -label=>'', -value=>'Y') : '',
                 popup_menu(-name=>"${ds}_Color", -class=>'small',
                            -id=>"${ds}_Color",
                            -values=>[sort Sort_Colors keys(%colors)],
                            -default=>param("${ds}_Color"),
                            -onchange=>"SetBgColor(this, \"${ds}_Colorize\")"));

        # Label/Format
        my $width = textfield(-name=>"${ds}_Width", -class=>'small',
                              -title=>'Width for TICK (0 to 1) and LINE? commands',
                              -id=>"${ds}_Width",
                              -size=>3,
                              -default=>param("${ds}_Width"));
        $width = '' unless ( $RRDs::VERSION >= 1.2 );
        print td(textfield(-name=>"${ds}_Label", -class=>'small',
                           -title=>'Graph label, format for GPRINT commands, offset for SHIFT commands',
                           -default=>param("${ds}_Label"),
                           -size=>15),
                 $width);

        # GPRINTs
        if ( $type ne 'evt' && $type ne 'rule' ) {
            print td({-align=>'center'},
                     checkbox_group(-name=>"${ds}_XTRAS",
                                    -values=>[@rranames],
                                    -labels=>\%rra,
                                    -defaults=>param("${ds}_XTRAS")));
        } else {
            print td({-align=>'center'}, 'N/A');
        }

        # BR
        print td({-align=>'center'},
                 checkbox(-name=>"${ds}_BR", -label=>'', -value=>'Y',
                          -title=>'Whether or not to add a line break (in the legend area) after this element'));

        print '<script type="text/javascript">TypeCB("'. param("${ds}_Type")
            .'", "'. $ds .'")</script>';

        print "</tr>\n\n";

        next unless ( $rowcount == 2 );

        if ( $RRDs::VERSION >= 1.2 ) {
            # STACK deprecated in 1.2
            @gt = grep(!/^(.RULE|SHIFT|STACK)$/, @graphtypes);
        } else {
            @gt = grep(!/^(.RULE|LINE\?|SHIFT|TICK)$/, @graphtypes);
        }
        $width = textfield(-name=>"${ds}_tWidth", -class=>'small',
                           -title=>'Width for TICK (0 to 1) and LINE? commands',
                           -id=>"${ds}_tWidth",
                           -size=>3,
                           -default=>param("${ds}_tWidth"))
            if ( $RRDs::VERSION >= 1.2 );
        print
            Tr({-class=>'small'},
               td({-align=>'center'}, 'N/A'),
               td(checkbox_group(-name=>"${ds}_tColors",
                                 -values=>[sort Sort_Colors keys(%colors)],
                                 -defaults=>param("${ds}_tColors"))),
               td($rranames{param("${ds}_RRA")}, br,
                  textfield(-name=>"${ds}_tCDEF", -class=>'small',
                            -size=>11, -default=>param("${ds}_tCDEF"))),
               td(popup_menu(-name=>"${ds}_tType", -class=>'small',
                             -values=>[@gt], -default=>param("${ds}_tType")),
                  radio_group(-name=>"${ds}_tSTACK", -label=>'',
                              -values=>[ 'N', 'Y', '+' ], -default=>'N',
                              -title=>'Whether to stack these elements on top of the previous one')),
               td(textfield(-name=>"${ds}_tLabel", -class=>'small',
                            -size=>15, -default=>param("${ds}_tLabel")),
                  $width),
               td({-align=>'center'},
                  checkbox_group(-name=>"${ds}_tXTRAS",
                                 -values=>[@rranames], -labels=>\%rra,
                                 -defaults=>param("${ds}_tXTRAS"))),
               td({-align=>'center'},
                  checkbox(-name=>"${ds}_tBR", -label=>'', -value=>'Y')));
    }

    print end_table;
    print <<END;
<script type="text/javascript">
var t;
i = 0;
while (i < 3) {
    t = document.getElementById("new" + i)
    t.innerHTML = "Click here to " + t.innerHTML.split("to", 2)[1]
    t.style.cursor = "pointer"
    t = document.getElementById("newrow" + i)
    t.style.display = "none"
    i = i + 1
}
DisableToggle("$new[0]")
DisableToggle("$new[1]")
DisableToggle("$new[2]")
</script>
END
    print
        p({-align=>'center'}, submit(-name=>'USERSAID',
                                     -value=>'Update'));
}

# GOptions: Lists general options for the user to set
sub GOptions
{
    my $nogridfit = Tr(td('No Grid Fit'),
                       td(checkbox(-name=>'gNoGridFit', -label=>'',
                                   -class=>'normal',
                                   -default=>param('gNoGridFit'))));
    $nogridfit = '' unless ( $RRDs::VERSION >= 1.2 );

    print
        table({-border=>1,-align=>'center'},
              caption(em(b('Graph Options'))),
              '<COLGROUP width="50%" align=right>',
              '<COLGROUP width="50%" align=center>',
              Tr(th({-align=>'center'}, 'Options'),
                 th({-align=>'center'}, 'Values')),
              Tr(td('Graph Title'),
                 td(textfield(-name=>'gTitle', -size=>30, -class=>'normal',
                              -default=>param('gTitle')))),
              Tr(td('Vertical Label'),
                 td(textfield(-name=>'gVLabel', -size=>30, -class=>'normal',
                              -default=>param('gVLabel')))),
              Tr(td('Base'),
                 td(popup_menu(-name=>'gBase',
                               -values=>['1000', '1024'],
                               -default=>param('gBase')))),
              Tr(td('Units Exponent'),
                 td(popup_menu(-name=>'gUExp',
                               -values=>['', '-18', '-15', '-12', '-9', '-6', '-3', '0', '3', '6', '9', '12', '15', '18'],
                               -default=>param('gUExp')))),
              Tr(td('Graph Width'),
                 td(textfield(-name=>'gWidth', -size=>30, -class=>'normal',
                              -default=>(param('gWidth') ? param('gWidth')
                                         : 500)))),
              Tr(td('Graph Height'),
                 td(textfield(-name=>'gHeight', -size=>30, -class=>'normal',
                              -default=>(param('gHeight') ? param('gHeight')
                                         : 120)))),
              Tr(td('Upper Limit', br, br, 'Lower Limit', br, br, 'Rigid Boundaries'),
                 td(textfield(-name=>'gYUp', -size=>30, -class=>'normal',
                              -default=>param('gYUp')), br,
                    textfield(-name=>'gYLow', -size=>30, -class=>'normal',
                              -default=>param('gYLow')), br,
                    checkbox(-name=>'gRigid', -label=>'', -class=>'normal',
                             -default=>param('gRigid')))),
              Tr(td('Y Grid'),
                 td(radio_group(-name=>'gYGrid', -class=>'normal',
                                -values=>['Auto', 'None', 'Alternate', 'MRTG'],
                                -default=>param('gYGrid')))),
              Tr(td('Alternate Autoscale'),
                 td(radio_group(-name=>'gAuto', -class=>'normal',
                                -values=>['No', 'Yes', 'Max Only'],
                                -default=>param('gAuto')))),
              Tr(td('Logarithmic Auto Scaling'),
                 td(checkbox(-name=>'gLog', -label=>'', -class=>'normal',
                             -default=>param('gLog')))),
              $nogridfit,
              Tr(td('Image Format'),
                 td(popup_menu(-name=>'gFormat', -class=>'normal',
                               -values=>[@ImgFormat],
                               -default=>param('gFormat')))),
              Tr(td('Dates'),
                 td(checkbox(-name=>'gDateStart', -label=>'Start',
                             -class=>'normal',
                             -default=>param('gDateStart')),
                    checkbox(-name=>'gDateEnd', -label=>'End',
                             -class=>'normal',
                             -default=>param('gDateEnd')),
                    checkbox(-name=>'gDateNow', -label=>'Now',
                             -class=>'normal',
                             -default=>param('gDateNow')))),
              Tr(td('Additional rrdgraph Options'),
                 td(textfield(-name=>'gRaw', -size=>30, -class=>'normal',
                              -default=>param('gRaw'))))),
              p({-align=>'center'}, submit(-name=>'USERSAID',
                                           -value=>'Update'));
}

sub DRAW_Element
{
    my ( $ds, $rra, $cdef, $type, $width, $stack, $color, $label, $br, @xtras ) = ( @_ );

    return () if ( $type eq '-Nothing-' );

    my @ELEM = ();
    my $gp = scalar(@xtras) - 1;

    $label =~ s/:/\\:/g;

    if ( $type eq 'COMMENT' ) {
        push @ELEM, join(':', $type, $label . (( $gp == -1 ) ? $br : ''));
    } elsif ( $type =~ /PRINT$/ ) {
        my @rra = ( $rra );
        @rra = () if ( $rra =~ /^.DEF$/ );
        push @ELEM, join(':', $type, (( $cdef eq '' ) ? $ds : uc($ds)), @rra,
                         $label . (( $gp == -1 ) ? $br : ''));
    } elsif ( $type =~ /RULE$/ ) {
        # HRULE is deprecated in 1.2
        $type = 'LINE1' if ( $type eq 'HRULE' && $RRDs::VERSION >= 1.2 );
        push @ELEM, join(':', $type, $cdef . $colors{$color},
                         $label . (( $gp == -1 ) ? $br : ''));
    } else {
        if ( &IsNum($cdef) ) {
            # It's a value, not a valid CDEF
            $ds = $cdef;
            $cdef = '';
        }
        $type = 'LINE'. $width if ( $type eq 'LINE?' );
        my @stack = ();
        @stack = ( 'STACK' ) if ( $stack );
        push @ELEM, join(':', $type,
                         (( $cdef eq '' ) ? $ds : uc($ds)) . $colors{$color},
                         (( $type eq 'TICK' ) ? $width .':' : '' )
                          . $label . (( $gp == -1 ) ? $br : ''), @stack);
    }

    return @ELEM if ( &IsNum($ds) );

    my $i = 0;
    while ( $i <= $gp ) {
        my $vname = ( $cdef eq '' ) ? $ds : uc($ds);
        my @rra = ( $xtras[$i] );
        if ( $RRDs::VERSION >= 1.2 ) {
            push @ELEM, 'VDEF:'. $vname .'_'. $xtras[$i]
                .'='. $vname .','. $xtras[$i]
                . ( ( length($xtras[$i]) == 3 ) ? 'IMUM' : '' );
            $vname .= '_'. $xtras[$i];
            @rra = ();
        }
        push @ELEM, join(':', 'GPRINT', $vname, @rra, $rranames{$xtras[$i]}
                         . "\\: ". $gformat ."%s" . (( $i == $gp ) ? $br : ''));
        $i += 1;
    }

    return @ELEM;
}

# DRAW: Build "rrdtool graph" arguments, and either call it or display them
# mode: 0 -> Dry run for errors, report any and optionally show the code
#   1 -> Produce image, be quiet about errors
#   2 -> Produce image, log errors to STDERR
sub DRAW
{
    croak 'DRAW(mode)' if ( scalar(@_) != 1 );
    my ( $mode ) = ( @_ );
    my ( @DEF, @CDEF, @ELEM, @Options ) = ( (), (), (), () );

    my ( $start, $end );
    $start = ( defined(param('Start')) && param('Start') ne '' ) ?
        param('Start') : 'end - 1 day';
    $end = ( defined(param('End')) && param('End') ne '' ) ?
        param('End') : 'now';
    
    my ( $startts, $endts ) = ( 0, time );
    if ( defined(&RRDs::times) ) {
        ( $startts, $endts ) = RRDs::times($start, $end);
        if ( defined(RRDs::error) ) {
            if ( $mode == 0 ) {
                &Error(RRDs::error);
                return 0;
            } else {
                die 'Failed to produce graph: '. RRDs::error;
            }
        }

        die "Invalid result ($startts) from RRDs::times()\n"
            unless ( $startts =~ /(\d+)/ );
        $startts = $1; # Untaint
        die "Invalid result ($endts) from RRDs::times()\n"
            unless ( $endts =~ /(\d+)/ );
        $endts = $1; # Untaint
    }

    my $pad = "\001";
    my $ds;
    foreach $ds ( sort { param("${a}_Seq") <=> param("${b}_Seq") }
                  keys(%DS) ) {
        if ( param("${ds}_File") !~ /\.evt$/ ) {
            if ( defined(param("${ds}_Formula")) ) {
                my $file;
                my $re = param("${ds}_File");
                my $count = 0;
                my @colors = param("${ds}_tColors");
                my ( @dscdefs, @dselements );
                foreach $file ( sort(@rrdfiles) ) {
                    next unless ( $file =~ /\.rrd$/ );
                    if ( $file =~ /$re/ ) {
                        # Don't consider stale files which have nothing
                        # interesting to contribute to the graph.
                        next unless ( (stat($file))[9] >= $startts );
                        next unless ( RRDs::last($file) >= $startts );

                        $file =~ s/:/\\:/g;

                        # Check for real values
                        my(@dsxz);
                        push @dsxz, join(':', 'DEF', 'xz='. $file,
                                         param("${ds}_DS"),
                                         param("${ds}_RRA"));
                        push @dsxz, join(':', 'PRINT', 'xz',
                                         param("${ds}_RRA"), '%lf');

                        my ($graphret, $xs, $ys) = RRDs::graph(( $Config{'osname'} eq 'MSWin32' ) ? 'NUL:' : '/dev/null', "--start", $startts, "--end", $endts, @dsxz);
                        my $any = 0;
                        foreach ( @{$graphret} ) {
                            next unless ( $_ ne "nan" && $_ > 0 );
                            $any = 1;
                            last;
                        }
                        next unless ( $any == 1 );

                        # Looks good, use it
                        push @DEF, join(':', 'DEF',
                                        $ds . $count .'='. $file,
                                        param("${ds}_DS"), param("${ds}_RRA"));
                        my $elmt = $ds . $count;
                        $elmt .= ','. param("${ds}_Element")
                            if ( param("${ds}_Element") ne '' );
                        $elmt =~ s/\$/$ds$count/g;
                        push @dselements, $elmt;

                        if ( param("${ds}_tCDEF") ne '' ) {
                            my $cdef = param("${ds}_tCDEF");
                            $cdef =~ s/\$/$ds$count/g;
                            push @dscdefs, join(':', 'CDEF',
                                                uc($ds . $count) .'='. $cdef);
                        }

                        my $label = param("${ds}_tLabel");
                        my @subs = ( $file =~ /$re/ );
                        $label = &ExpandMatches($label, \@subs);
                        $pad = "" if ( $label =~ /(%|\\)/ || $label =~ /^\s*$/);
                        my ( $type, $stack ) = ( param("${ds}_tType"), 0 );
                        if ( defined(param("${ds}_tSTACK")) ) {
                            if ( ( $count > 0 && param("${ds}_tSTACK") eq 'Y' )
                                 || param("${ds}_tSTACK") eq '+' ) {
                                if ( $RRDs::VERSION >= 1.2 ) {
                                    $stack = 1;
                                } else {
                                    $type = 'STACK';
                                }
                            }
                        }
                        push @ELEM, &DRAW_Element($ds . $count,
                                                  param("${ds}_RRA") .'',
                                                  param("${ds}_tCDEF"),
                                                  $type,
                                                  param("${ds}_tWidth"),
                                                  $stack,
                                                  ( scalar(@colors) == 0 ) ?
                                                  param("${ds}_Color") :
                                          $colors[$count%scalar(@colors)],
                                                  $pad . $label . $pad,
                                                  defined(param("${ds}_tBR")) ?
                                                  '\n' : '',
                                                  param("${ds}_tXTRAS"));
                        $pad = ( defined(param("${ds}_tBR")) ) ? "\001" : "";

                        $count += 1;
                    }
                }
                foreach ( @dscdefs ) {
                    $_ =~ s/\#/$count/g;
                    push @CDEF, $_;
                }
                if ( param("${ds}_Formula") ne '' ) {
                    my $dsdef = '';
                    my $i;
                    my $first = shift @dselements;
                    if ( param("${ds}_Formula") eq 'AVERAGE' ) {
                        $dsdef  = "$first,". join(",+,", @dselements);
                        $dsdef .= ",+,$count,/";
                    } elsif ( param("${ds}_Formula") eq 'STDDEV' ) {
                        my ( $sum, $sumsq );
                        $sum    = "$first,". join(",+,", @dselements) .",+";
                        $sumsq  = "$first,DUP,*,";
                        $sumsq .= join(",DUP,*,+,", @dselements) .",DUP,*,+";
                        $dsdef  = "$sumsq,$count,/";
                        $dsdef .= ",$sum,$count,/,DUP,*";
                        $dsdef .= ",-";
                        $dsdef .= ",SQRT";
                    } else {
                        $dsdef  = "$first,";
                        $dsdef .= join(",". param("${ds}_Formula") .",",
                                      @dselements);
                        $dsdef .= ",". param("${ds}_Formula")
                    }
                    $dsdef =~ s/\#/$count/g;
                    push @CDEF, join(':', 'CDEF', $ds .'='. $dsdef);
                }
            } else {
                my $file = param("${ds}_File");
                $file =~ s/:/\\:/g;
                push @DEF, join(':', 'DEF', $ds .'='. $file,
                                param("${ds}_DS"), param("${ds}_RRA"))
                    unless ( param("${ds}_File") eq '' );
            }
            if ( param("${ds}_CDEF") ne ''
                 && param("${ds}_Type") !~ /RULE$/
                 && !&IsNum(param("${ds}_CDEF")) ) {
                my $cdef = param("${ds}_CDEF");
                $cdef =~ s/\$/$ds/g;
                push @CDEF, join(':', ( param("${ds}_RRA") eq 'VDEF' )
                                 ? 'VDEF' : 'CDEF', uc($ds) .'='. $cdef);
            }
            if ( param("${ds}_Type") eq 'SHIFT' ) {
                if ( param("${ds}_CDEF") eq ''
                     && param("${ds}_Formula") eq '' ) {
                    push @DEF,  join(':', 'SHIFT', $ds, param("${ds}_Label"));
                } else {
                    push @CDEF,  join(':', 'SHIFT',
                                      ( param("${ds}_Formula") eq '' ) ?
                                      uc($ds) : $ds,
                                      param("${ds}_Label"));
                }
                next;
            }
            $pad = ""
                if ( param("${ds}_Label") =~ /(%|\\)/
                     || param("${ds}_Label") =~ /^\s*$/ );
            push @ELEM, &DRAW_Element($ds, param("${ds}_RRA") .'',
                                      param("${ds}_CDEF"),
                                      param("${ds}_Type"),
                                      param("${ds}_Width"),
                                      defined(param("${ds}_STACK")),
                                      param("${ds}_Color"),
                                      $pad . param("${ds}_Label") . $pad,
                                      defined(param("${ds}_BR")) ? '\n' : '',
                                      param("${ds}_XTRAS"));
            $pad = ( defined(param("${ds}_BR")) ) ? "\001" : "";
        } else {
            open EVENTS, "< " . param("${ds}_File")
                or &Error("Failed to open ". param("${ds}_File") . ": $!\n");
            my $format;
            while (<EVENTS>) {
                chomp;
                next if ( /^\#/ );
                if ( /^(\d+)\s+(.+)$/ ) {
                    my ( $ts, $msg ) = ( $1, $2 );
                    next if ( defined(&RRDs::times)
                              && ( $ts < $startts || $ts > $endts ) );
                    my $re = param("${ds}_EvtFilter");

                    if ( !defined($format) ) {
                        # Try to be smart about the timestamps format
                        my $now = time;
                        if ( $now - $ts > 30 * 86400 ) {
                            # More than a month ago..
                            $format = "%Y-%m-%d %H:%M";
                        } elsif ( $now - $ts > 7 * 86400 ) {
                            # More than a week ago..
                            $format = "%a %e %H:%M";
                        } else {
                            $format = "%a %H:%M";
                        }
                    }

                    $msg = '['. strftime($format, localtime($ts)) .'] '. $msg;
                    next unless ( $re eq '' || $msg =~ /$re/ );
                    $msg = param("${ds}_Label")
                        if ( defined(param("${ds}_Label"))
                             && param("${ds}_Label") ne '' );
                    $msg =~ s/:/\\:/g;
                    $msg .= '\n' if ( defined(param("${ds}_BR")) );
                    push @ELEM, join(':', 'VRULE',
                                     $ts . $colors{param("${ds}_Color")},$msg);
                } else {
                    &Error("Bad Entry in ". param("${ds}_File") . ": $_\n");
                }
            }
            close EVENTS;
        }
    }

    my $i = 0;
    my $maxlen = 0;
    while ( $i < scalar(@ELEM) ) {
        if ( $ELEM[$i] =~ /\001(.+)\001/ ) {
            $maxlen = length($1) if ( $maxlen < length($1) );
        }
        $i += 1;
    }
    $i = 0;
    while ( $i < scalar(@ELEM) ) {
        if ( $ELEM[$i] =~ /\001(.+)\001/ ) {
            $pad = ' ' x ( $maxlen - length($1) );
            $pad .= '  ' if ( $ELEM[$i] =~ /^COMMENT:/ );
            $ELEM[$i] =~ s/\001(.+)\001/$1$pad/;
        }
        $i += 1;
    }

    if ( defined(param('gDateStart')) && defined(param('gDateEnd')) ) {
        push @ELEM, "COMMENT:".
            strftime("%a %Y-%m-%d %H\\:%M", localtime($startts)) .
            " - ". 
            strftime("%a %Y-%m-%d %H\\:%M", localtime($endts)) ."\\c";
    } elsif ( defined(param('gDateStart')) ) {
        push @ELEM, "COMMENT:". strftime("%a %Y-%m-%d %H\\:%M",
                                            localtime($startts)). "\\n";
    } elsif ( defined(param('gDateEnd')) ) {
        push @ELEM, "COMMENT:". strftime("%a %Y-%m-%d %H\\:%M",
                                            localtime($endts))  ."\\r";
    }
    if ( defined(param('gDateNow')) ) {
        push @ELEM, "COMMENT:Created on". strftime("%a %Y-%m-%d %H\\:%M",
                                                   localtime($endts))  ."\\r";
    }

    push @Options, '--start='. $start;
    push @Options, '--end='. $end;
    my $option;
    foreach $option ( keys(%goptions) ) {
        push @Options, $goptions{$option} . '=' . param("$option")
            if ( defined(param("$option")) && param("$option") ne '' );
    }
    push @Options, '--rigid' if ( defined(param('gRigid')) );
    if ( param('gYGrid') eq 'None' ) {
        push @Options, '--y-grid', 'none';
    } elsif ( param('gYGrid') eq 'Alternate' ) {
        push @Options, '--alt-y-grid';
    } elsif ( param('gYGrid') eq 'MRTG' ) {
        push @Options, '--alt-y-mrtg';
    }
    if ( param('gAuto') eq 'Yes' ) {
        push @Options, '--alt-autoscale';
    } elsif ( param('gAuto') eq 'MaxOnly' ) {
        push @Options, '--alt-autoscale-max';
    }
    push @Options, '--logarithmic' if ( defined(param('gLog')) );
    push @Options, '--no-gridfit'
        if ( defined(param('gNoGridFit')) && $RRDs::VERSION >= 1.2 );
    push @Options, '--interlaced';
    push @Options, '--no-legend' if ( defined(param('gNoLegend')) );
    push @Options, split(/ /, param('gRaw')) if ( defined(param('gRaw')) );

    my ( $out, $ttl );
    if ( $mode > 0 ) {
        if ( defined(&RRDs::times) ) {
            if ( defined(param('gWidth')) && param('gWidth') =~ /(\d+)/ ) {
                $ttl = int(($endts - $startts + 1) / $1); # Untaint
            } else {
                $ttl = int(($endts - $startts + 1) / 400 );
            }
        } else {
            # Find out whether this is a configured view
            my $view = scalar(@dv_name);
            if ( defined(param('Start')) && $end eq 'now' ) {
                $view = 0;
                while ( $view < scalar(@dv_name) ) {
                    last if ( $dv_def[$view] eq $start );
                    $view += 1;
                }
            }
            if ( $view >= scalar(@dv_secs) ) {
                # TTL defaults to 5m
                $ttl = 300;
            } else {
                if ( defined(param('gWidth')) && param('gWidth') =~ /(\d+)/ ) {
                    $ttl = int($dv_secs[$view] / $1); # Untaint
                } else {
                    $ttl = int($dv_secs[$view] / 400);
                }
            }
        }
        $ttl = 60 if ( $ttl < 60 ); # Enforce a sane 1m minimum

        # Now, let's figure out if there's anything usable cached
        if ( $clean_cache ) {
            my $cid = Digest::MD5::md5_hex(@Options, @DEF, @CDEF, @ELEM);

            my @cached = glob($tmp_dir ."/cached-image-". $cid ."-*");
            while ( scalar(@cached) > 0 ) {
                my $hit = $cached[0];
                $hit =~ s/.+cached-image-[^-]+-//;
                if ( $hit eq '' ) {
                    if ( $cached[0] =~ /(.+)/
                         && (time - (stat($cached[0]))[9]) > 900 ) {
                        unlink $1; # Untaint
                    }
                } elsif ( -z $cached[0] || $hit < time ) {
                    # Delete any file expired for over 2 minutes
                    if ( $cached[0] =~ /(.+)/ && time - $hit > 120 ) {
                        unlink $1; # Untaint
                    }
                } else {
                    # Usable cached file, is it the latest?
                    $out = $cached[0]
                        unless ( defined($out)
                                 && (stat($out))[9] > (stat($cached[0]))[9] );
                }
                shift @cached;
            }

            if ( !defined($out) || ! -r $out ) {
                # Nothing usable in cache, will have to generate a new file
                $cid =~ /(.+)/;
                $out = $tmp_dir ."/cached-image-". $1 ."-"; # Untaint
            }
        } else {
            $out = '-';
            print header(-type=>$Mime{param('gFormat')},
                         -expires=>'+'. $ttl .'s',
                         -last_modified=>&time2str());
        }
    } elsif ( $Config{'osname'} eq 'MSWin32' ) {
        $out = 'NUL:';
    } else {
        $out = '/dev/null';
    }

    RRDs::graph($out, @Options, @DEF, @CDEF, @ELEM)
        unless ( $mode > 0 && $out ne '-' && -r $out );

    if ( defined(RRDs::error) ) {
        if ( $mode == 0 ) {
            &Error(RRDs::error);
        } else {
            if ( $mode == 2 ) {
                warn 'Failed to produce graph: ' . RRDs::error . "\n";
            }
            unlink $out # Wipe out whatever may have been created on disk
                unless ( $out eq '-' );
        }
    } elsif ( $mode > 0 && $out ne '-' ) {
        my $lm = &time2str((stat($out))[9]);
        if ( $out =~ /-$/ ) {
            # New cache file, let's rename it so that others can use it.
            my $new = $out . ( time + $ttl );
            if ( !rename($out, $new) ) {
                warn 'Failed to rename temporary cache file "'.
                    $out .'": '. "$!\n";
                open IMG, "< $out" or
                    die "Failed to open temporary cached file $out: $!\n";
            } else {
                open IMG, "< $new"
                    or die "Failed to open renamed cached file $new: $!\n";
            }
            $out = time + $ttl;
        } else {
            open IMG, "< $out"
                or die "Failed to open existing cached file $out: $!\n";
            # Extract the expiration date
            $out =~ s/.+cached-image-[^-]+-//;
        }
        binmode(IMG);
        if ( !defined($ENV{'HTTP_IF_MODIFIED_SINCE'})
             || $ENV{'HTTP_IF_MODIFIED_SINCE'} ne $lm ) {
            print header(-type=>$Mime{param('gFormat')},
                         -expires=>&time2str($out), -last_modified=>$lm);
            while (<IMG>) { print; };
        } else {
            # Apache would do this for us, but why bother sending the data?
            print header(-status=>'304',
                         -type=>$Mime{param('gFormat')},
                         -expires=>&time2str($out), -last_modified=>$lm);
        }
        if ( !close(IMG) ) {
            warn "Problems while sending cached file: $!\n";
        }

        if ( ( ! -e "${tmp_dir}/purged"
               || time - (stat($tmp_dir .'/purged'))[9] > $clean_cache )
             && open(TS, "> ${tmp_dir}/purged") ) {
            print TS "oink!\n";
            close TS;
            my @cached = glob($tmp_dir ."/cached-image-*");
            while ( scalar(@cached) > 0 ) {
                my $expires = $cached[0];
                $expires =~ s/.+cached-image-[^-]+-//;
                if ( $expires eq '' ) {
                    # Should only happen for files _being_ generated, but..
                    if ( $cached[0] =~ /(.+)/
                         && (time - (stat($cached[0]))[9]) > 900 ) {
                        unlink $1; # Untaint
                    }
                } elsif ( $expires < time ) {
                    # Delete any file expired for over 2 minutes
                    if ( $cached[0] =~ /(.+)/ && time - $expires > 120 ) {
                        unlink $1; # Untaint
                    }
                }
                shift @cached;
            }
        }
    }

    if ( $mode == 0 ) {
        if ( defined(param('Code')) && param('Code') eq 'Show' ) {
            print
                pre({-class=>'code', -id=>'CODE'},
                    "rrdtool graph - \\\n "
                    . join(" \\\n ", @Options, @DEF, @CDEF, @ELEM));
        } else {
            print
                pre({-class=>'code', -id=>'CODE', -style=>'display:none'},
                    "rrdtool graph - \\\n "
                    . join(" \\\n ", @Options, @DEF, @CDEF, @ELEM));
        }
    }

    return 0 if ( defined(RRDs::error) );
    return 1;
}

# GraphHTML: Returns HTML to include a graph in a page
sub GraphHTML
{
    croak 'GraphHTML(name, base, [start [, end, [width, height, nolegend]]])'
        if ( scalar(@_) < 2 || scalar(@_) > 7 );
    my ( $name, $base, $start, $end, $width, $height, $nolegend ) = ( @_ );

    my $query = new CGI;

    $query->delete_all;
    $query->param('Mode', 'show');
    if ( $name !~ /^t/ ) {
        $query->param('Graph', $name);
    } else {
        $query->param('Template', $name);
        $query->param('Base', $base) if ( defined($base) );
    }
    $query->param('Start', $start) if ( defined($start) );
    $query->param('End', $end) if ( defined($end) );
    $query->param('Width', $width) if ( defined($width) );
    $query->param('Height', $height) if ( defined($height) );
    $query->param('NoLegend', 1) if ( defined($nolegend) );

    # Image or Other?
    my $type = Definition_Get($name, 'gFormat');
    my $url = $query->url(-path_info=>1, -query=>1, -relative=>1);
    if ( !defined($type) || $type =~ /^(PNG|GIF)/ ) {
        return img({-src=>$url,-align=>'center', -border=>0,
                    -onerror=>'this.onerror=null; this.src="/icons/unknown.gif"'});
    } elsif ( $type eq 'SVG' ) {
        return "<object data='$url' type='image/svg+xml' align='center'><embed src='$url' type='image/svg+xml' align='center'><noembed>Your browser does not support embedded $type files.</noembed></embed></object>";
    } else {
        # Could we do better? Do we care to?
        return a({-href=>$url}, "$type Document: '".
                 Definition_Get($name, 'gTitle') ."'");
    }
}

sub TMPLFind
{
    croak 'TMPLFind(filter, display)'
        unless ( scalar(@_) == 0 || scalar(@_) == 2 );
    my ( $ex, $nex ) = ( @_ );

    my %once;
    %TMPL = ();
    return unless ( defined($ex) && $ex ne '' );
    foreach ( @rrdfiles ) {
        my $label = $_;
        my $base = $_;
        $label =~ s/.*\/\///;
        my @subs = ( $label =~ $ex );
        next unless ( scalar(@subs) > 0 );
        if ( defined($nex) && $nex ne '' ) {
            $label = &ExpandMatches($nex, \@subs);
        } else {
            $label = join(' - ', ($label =~ /$ex/));
        }
        next if ( defined($once{$label}) );
        $once{$label} = 1;
        $TMPL{$base} = $label;
    }
}

sub TMPLConfig
{
    print p(table({-width=>'70%', -align=>'center'},
                  Tr(td({-class=>'help'}, &help_templates))))
        if ( param('USERSAID') eq 'Make Template' );

    print
        p({-align=>'center'}, submit(-name=>'USERSAID',
                                     -value=>'Update')),
        start_table({-border=>1,-align=>'center'},
                    caption(em(b('Template Settings')),
                            a({-href=>MakeURL('Browse', 'Help') .'#templates',
                               -target=>'drrawhelp'}, small('Help'))),
                    '<COLGROUP width="50%" align=right>',
                    '<COLGROUP width="50%" align=center>'),
        Tr(th({-align=>'center'}, 'Setting'),
           th({-align=>'center'}, 'Values')),
        Tr(td('Template Name'),
           td(textfield(-name=>'tName', -size=>40, -class=>'normal',
                        -default=>param('tName')))),
        Tr(td('Base Regular Expression'),
           td(textfield(-name=>'tRegex', -size=>40, -class=>'normal',
                        -default=>param('tRegex')))),
        Tr(td('Selection Regular Expression'),
           td(textfield(-name=>'tNiceRegex', -size=>40, -class=>'normal',
                        -default=>param('tNiceRegex'))));

    my ( %once, $ds, @subs );
    if ( scalar(keys(%TMPL)) > 0 ) {
        my ( @tmp, $best, @count );
        $best = 0;
        # It'd work better with two passes, but this should be enough
        foreach $ds ( sort keys(%DS) ) {
            next if ( param("${ds}_File") eq '' );
            next if ( defined(param("${ds}_Formula")) );
            my $ex = param('tRegex');
            my $dbname = substr(param("${ds}_File"),
                                2+index(param("${ds}_File"), '//'));
            @tmp = ( $dbname =~ /$ex/ );
            next if ( scalar(@tmp) == 0 );
            if ( !defined(param("${ds}_Tmpl")) || param("${ds}_Tmpl") eq '' ) {
                param(-name=>"${ds}_Tmpl",
                      -value=>&ReduceMatches($dbname, \@tmp));
            } else {
                next unless ( $dbname eq &ExpandMatches(param("${ds}_Tmpl"),
                                                        \@tmp) );
            }
            @count = ( param("${ds}_Tmpl") =~ /(\$\d+)/g );
            if ( scalar(@count) > $best ) {
                @subs = @tmp;
                $best = scalar(@count);
            }
        }
    }

    foreach $ds ( sort keys(%DS) ) {
        next if ( param("${ds}_File") eq '' );
        next if ( $once{$DS{$ds}} );
        $once{$DS{$ds}} = 1;
        my $class = 'normal';
        my $dbname;
        if ( !defined(param("${ds}_Formula")) ) {
            $dbname = substr(param("${ds}_File"),
                             2+index(param("${ds}_File"),'//'));
        } else {
            $dbname = param("${ds}_File");
        }
        if ( scalar(@subs) > 0 ) {
            if ( param("${ds}_Tmpl") eq '' ) {
                param(-name=>"${ds}_Tmpl",
                      -value=>&ReduceMatches($dbname, \@subs));
            } else {
                $class = 'smallred' if ( $dbname ne &ExpandMatches(param("${ds}_Tmpl"), \@subs) );
            }
        } else {
            $class = 'red';
        }
        print
            Tr(td($dbname),
               td(textfield(-name=>"${ds}_Tmpl", -class=>$class, -size=>40,
                            -default=>param("${ds}_Tmpl"))));
    }

    print
        end_table,
        p({-align=>'center'}, submit(-name=>'USERSAID',
                                     -value=>'Update'));

    if ( defined(param('tRegex')) && param('tRegex') ne '' ) {
        if ( scalar(keys(%TMPL)) == 0 ) {
            &Error("Base Regular Expression produced no match!");
        } else {
            print
                p({-align=>'center'},
                  'There were ' . scalar(keys(%TMPL))
                  . ' match for the Base Regular Expression: ',
                  popup_menu(-name=>'TempPopup', -class=>'small',
                             -values=>[sort(keys(%TMPL))]));

        }
    }
}

sub BoardFind
{
    croak 'BoardFind(board)'
        unless ( scalar(@_) == 1 );
    my ( $board ) = ( @_ );

    die "Undefined board: ${board}" unless ( defined($BoardsById{$board}) );

    %DBTMPL = ();
    return unless ( defined($BoardsById{$board}{'Filters'}) );

    my $item;
    foreach $item ( keys(%{$BoardsById{$board}{'Filters'}}) ) {
        &TMPLFind($TemplatesById{$BoardsById{$board}{'Filters'}{$item}{'Template'}}{'Filter'}, $TemplatesById{$BoardsById{$board}{'Filters'}{$item}{'Template'}}{'Display'});
        my $key;
        foreach $key ( keys(%TMPL) ) {
            my $filter = $BoardsById{$board}{'Filters'}{$item}{'Filter'};
            next if ( defined($filter) && $filter ne ''
                      && $TMPL{$key} !~ /$filter/ );
            my $display = $TMPL{$key};
            $filter = $BoardsById{$board}{'Filters'}{$item}{'Display'};
            $display = join(' - ', ($display =~ /$filter/))
                if ( defined($filter) && $filter ne '' );
            $DBTMPL{$display}{$item} = $key;
        }
    }
}

sub BoardOptions
{
    print p(table({-width=>'70%', -align=>'center'},
                  Tr(td({-class=>'help'}, &help_dbintro))))
        unless ( scalar(grep(/_Seq/, param())) > 0 );
        
    print
        table({-border=>1,-align=>'center'},
              caption(em(b('Dashboard Options'),
                         a({-href=>MakeURL('Browse', 'Help') . '#dbintro',
                            -target=>'drrawhelp'}, small('Help')))),
              '<COLGROUP width="50%" align=right>',
              '<COLGROUP width="50%" align=center>',
              Tr(th({-align=>'center'}, 'Options'),
                 th({-align=>'center'}, 'Values')),
              Tr(td('Dashboard Title'),
                 td(textfield(-name=>'dTitle', -size=>20,
                              -default=>param('dTitle')))),
              Tr(td('Type'),
                 ( !defined(param('dGrouped')) ) ?
                 td(b('Standard'), submit(-name=>'USERSAID', -value=>'Switch'))
                 : td(b('Grouped'),
                      submit(-name=>'USERSAID', -value=>'Switch'))),
              ( defined(param('dGrouped')) ) ? ''
              : Tr(td('Columns'),
                   td(textfield(-name=>'dCols', -size=>20,
                                -default=>(param('dCols') ? param('dCols')
                                           : 2)))),
              Tr(td('Preview Width'),
                 td(textfield(-name=>'dWidth', -size=>20,
                              -default=>(param('dWidth') ? param('dWidth')
                                         : 300)))),
              Tr(td('Preview Height'),
                 td(textfield(-name=>'dHeight', -size=>20,
                              -default=>(param('dHeight') ? param('dHeight')
                                         : 100)))),
              Tr(td('Suppress Legends'),
                 td(radio_group(-name=>'dNoLegend',
                                -values=>['On', 'Off'],
                                -default=>param('dNoLegend')))),
              Tr(td('Default View'),
                 td(popup_menu(-name=>'dView',
                               -values=>[@dv_name]))));
}

sub BoardConfig
{
    my $item;

    # First add 10 more rows
    my $seq = 1;
    $item = 'a';
    while ( $seq++ < 10 ) {
        while ( defined(param("${item}_Seq")) ) {
            if ( $item =~ /(z+)$/ ) {
                my $zzz = $1;
                $zzz =~ s/z/a/g;
                substr($item, length($item)-length($zzz)) = $zzz;
                if ( length($zzz) < length($item) ) {
                    my $chr = substr($item, length($item)-length($zzz)-1, 1);
                    substr($item, length($item)-length($zzz)-1, 1) = chr(ord($chr) + 1);
                } else {
                    $item .= 'a';
                }
            } else {
                my $chr = substr($item, length($item)-1, 1);
                substr($item, length($item)-1) = chr(ord($chr) + 1);
            }
        }
        param("${item}_DELETE", 'Y');
        param("${item}_Seq", 99999);
        param("${item}_new", 1);
    }

    # Second, get the labels ready
    my %pretty;
    if ( !defined(param('dGrouped')) ) {
        foreach ( keys(%GraphsById) ) {
            $pretty{'g' . $_} = $GraphsById{$_}{'Name'} .' (Graph)';
        }
    }
    foreach ( keys(%TemplatesById) ) {
        $pretty{'t' . $_} = $TemplatesById{$_}{'Name'} . ' (Template)';
    }

    print
        p(table({-width=>'70%', -align=>'center'},
                Tr(td({-class=>'help'}, &help_dbconfig))))
        unless ( scalar(grep(/_Seq/, param())) > 0
                 && param('USERSAID') ne 'Switch' );

    print
        start_table({-border=>1, -align=>'center'}),
        caption(em(b('Dashboard Configuration'),
                   a({-href=>MakeURL('Browse', 'Help') . '#dbconfig',
                      -target=>'drrawhelp'}, small('Help')))),
        Tr(th(['DEL ?', 'Seq',
               ( defined(param('dGrouped')) ) ? 'Template' . br
               . 'Available Template Selections'
               : 'Graph / Template' . br . 'Available Template Selections',
               'Template Style', 'Template Base',
               ( defined(param('dGrouped')) ) ? 'Row Name'
               : 'Selection Regular Expression']));

    $seq = 1;
    foreach $item ( sort { param($a) <=> param($b) }
                    grep(/^[a-z]+_Seq$/, param()) ) {
        $item =~ s/_Seq//;
        next if ( defined(param("${item}_DELETE"))
                  && !defined(param("${item}_new")) );
        next if ( defined(param('dGrouped'))
                  && defined(param("${item}_dname"))
                  && param("${item}_dname") !~ /^t/ );
        param("${item}_Seq", $seq); $seq += 1; # Automatic resequencing
        my @tstyles = ( 'List', 'Regex', 'All' );
        push @tstyles, ( 'Base' ) unless ( defined(param('dGrouped')) );

        my $tmpl = param("${item}_dname");
        if ( defined($tmpl) && $tmpl =~ /^t/ ) {
            $tmpl =~ s/^t//;
            &TMPLFind($TemplatesById{$tmpl}{'Filter'},
                      $TemplatesById{$tmpl}{'Display'});
            $tmpl = br . 'Selection: ' .
                popup_menu(-name=>"${item}_tlist", -class=>'small',
                           -values=>[sort { $TMPL{$a} cmp $TMPL{$b} }
                                     keys(%TMPL)],
                           -labels=>\%TMPL);
        } else {
            $tmpl = '';
        }

        my $class = 'small';
        if ( !defined(param("${item}_new"))
             && !defined($pretty{param("${item}_dname")}) ) {
            param(-name=>"${item}_DELETE", -value=>'Y');
            $class = 'smallred';
        }

        print
            '<tr>',
            td({-align=>'center'},
               checkbox(-name=>"${item}_DELETE", -label=>'', -value=>'Y',
                        -onclick=>"DisableToggle(\"${item}\")")),
            td({-align=>'center'},
               textfield(-name=>"${item}_Seq", -class=>'small',
                         -default=>param("${item}_Seq"),
                         -size=>3)),
            td(popup_menu(-name=>"${item}_dname", -class=>$class,
                          -values=>[sort { $pretty{$a} cmp $pretty{$b} }
                                    (keys(%pretty))],
                          -labels=>\%pretty,
                          -default=>param("${item}_dname"),
                          -onchange=>"ToggleDB(\"${item}\")") . $tmpl),
            td({-align=>'center'},
               popup_menu(-name=>"${item}_type", -class=>'small',
                          -values=>\@tstyles));
        if ( ( defined(param("${item}_dname"))
               && param("${item}_dname") =~ /^g/ ) 
             || ( defined(param("${item}_type")) 
                  && param("${item}_type") eq 'All' ) ) {
            print td({-align=>'center', -class=>'small'}, 'N/A');
        } elsif ( defined(param("${item}_type"))
             && param("${item}_type") eq 'List' ) {
            my $tmpl = param("${item}_dname");
            $tmpl =~ s/^t//;
            &TMPLFind($TemplatesById{$tmpl}{'Filter'},
                      $TemplatesById{$tmpl}{'Display'});
            print
                td({-align=>'center'},
                   scrolling_list(-name=>"${item}_list", -class=>'small',
                                  -values=>[sort { $TMPL{$a} cmp $TMPL{$b} }
                                            keys(%TMPL)],
                                  -labels=>\%TMPL,
                                  -size=>5,
                                  -multiple=>'true'));
        } else {
            print
                td({-align=>'center'},
                   textfield(-name=>"${item}_regex", -class=>'small',
                             -default=>param("${item}_regex")));
        }
        if ( defined(param('dGrouped'))
             || ( defined(param("${item}_dname"))
                  && param("${item}_dname") =~ /^t/
                  && param("${item}_type") eq 'Base' ) ) {
            print
                td({-align=>'center'},
                   textfield(-name=>"${item}_row", -class=>'small',
                             -default=>param("${item}_row")));
        } else {
            print td({-align=>'center', -class=>'small'}, 'N/A');
        }
    }

    print
        end_table,
        p({-align=>'center'}, submit(-name=>'USERSAID',
                                     -value=>'Update'));
}

#
# Log related functions
#
sub ShowLog
{
    print h2('ChangeLog');

    if ( !open(LOG, "< ${saved_dir}/log") ) {
        print "No log available: $!";
    } else {
        my ( %titles, @entry );
        my $page = ( defined(param('Page')) ) ? param('Page') : 0;
        print '<p align=center>';
        print a({-href=>&MakeURL('Browse', 'Log', 'Page', $page - 1)}, 'Next')
            if ( $page > 0 );
        while (<LOG>) {
            chomp;
            my ( $ts, $id, $who, $title ) = split(/\|/);
            unshift @entry, ( $ts, $id, $who, $title, $titles{$id} );
            $titles{$id} = $title;
        }
        close LOG;
            
        print ' ';
        print a({-href=>&MakeURL('Browse', 'Log', 'Page', $page + 1)},
                'Previous')
            if ( scalar(@entry) > 250 );
        print
            '</p>',
            start_table({-border=>1, -align=>'center'}),
            Tr(th(['Date', 'Item', 'Title', 'Updated By', 'Old Title']));

        $page *= 50;
        while ( scalar(@entry) > 0 ) {
            my ( $ts, $id, $who, $title, $old ) =
                ( shift @entry, shift @entry, shift @entry,
                  shift @entry, shift @entry );
            next unless ( $page-- <= 0 );
            my $url = &MakeURL('Mode', 'view',
                               ( $id =~ /^g/ ) ? 'Graph'
                               : ( $id =~ /^t/ ) ? 'Template'
                               : 'Dashboard', substr($id, 1));
            print
                Tr(td(strftime("%Y-%m-%d %H:%M", localtime($ts)) .''),
                   td((-e "$saved_dir/RCS/$id,v") ? 
                      a({-href=>MakeURL('Browse', 'Rcs', 'Id', $id)},
                        img({-src=>$icon_text, -border=>0})) : '',
                      (-e "$saved_dir/$id") ? a({-href=>$url}, $id) : $id),
                   td((-e "$saved_dir/$id") ? a({-href=>$url}, $title):$title),
                   td(( $who ne '' ) ? $who : '&nbsp;'),
                   td(( defined($old) && $title ne $old ) ? $old : '&nbsp;'));
            last if ( $page == -50 );
        }
        print
            end_table, p;
    }
    print
        $drrawhome,
        $footer,
        end_html;
}

sub ShowRcsLog
{
    print h2('RCS Log');

    if ( ! $use_rcs ) {
        &Error("Rcs module is missing.");
    } elsif ( !defined(param('Id')) || param('Id') !~ /^([tgd]\d+\.\d+)$/ ) {
        &Error("Invalid Request");
    } else {
        my $rcs = new Rcs;
        $rcs->file($1);
        $rcs->workdir($saved_dir);
        $rcs->rcsdir($saved_dir .'/RCS');
        my @rlog = $rcs->rlog;

        my $show = 0;
        print start_ul;
        while ( scalar(@rlog) > 0 ) {
            $_ = shift @rlog;
            chomp;
            $show = 1 if ( /^revision / );
            next unless ( $show );
            next if ( /^[-=]+$/ );
            if ( /^revision (\S+)$/ ) {
                print '<li> revision '. b($1);
            } elsif ( /^date: ([^;]+); +author: ([^;]+);/ ) {
                print ' on '. b($1) .' by '. b($2) .'<br>';
            } else {
                print;
            }
        }
        print end_ul;
    }
    print
        $drrawhome,
        $footer,
        end_html;
}

#
# Utility functions
#

sub MakeURL
{
    confess 'MakeURL(name1, value1, ...)' unless ( scalar(@_) % 2 == 0 );

    my $query = new CGI;
    $query->delete_all;
    while ( scalar(@_) > 0 ) {
        if ( defined($_[1]) ) {
            if ( ref($_[1]) eq 'ARRAY' ) {
                $query->param(-name=>shift @_, -value=>shift @_);
            } else {
                $query->param(shift @_, shift @_);
            }
        } else {
            shift @_; shift @_;
        }
    }
    return $query->url(-path_info=>1, -query=>1, -relative=>1);
}

# Courtesy of Cliff Miller
sub Sort_Colors_Init
{
    my $color;
    foreach $color ( keys(%colors) ) {
        if ( $colors{$color} eq '' ) {
            $colorsidx{$color} = 301;
            next;
        }
        my $x = hex(substr($colors{$color}, 1, 2)) / 256 - 0.5;
        my $y = hex(substr($colors{$color}, 3, 2)) / 256 - 0.5;
        my $z = hex(substr($colors{$color}, 5, 2)) / 256 - 0.5;
        if ( $x == $y && $y == $z ) {
            $colorsidx{$color} = 300 - hex(substr($colors{$color}, 1, 2));
        } else {
            my $nx = sqrt(2)/2 * ( $x - $y );
            my $ny = sqrt(2)/2 * sqrt(3)/3 * ( $x + $y ) - sqrt(6)/3 * $z;
            $colorsidx{$color} = atan2($ny, $nx);
        }
    }
}

sub Sort_Colors
{
    return $colorsidx{$a} <=> $colorsidx{$b};
}

sub DSNew
{
    croak 'DSNew(file, DSname, CFname)' if ( scalar(@_) != 3);

    my ( $rrdfile, $DSname, $CFname ) = ( @_ );

    my $len;
    my $name = 'a';
    while ( defined($DS{$name}) ) {
        if ( $name =~ /(z+)$/ ) {
            my $zzz = $1;
            $zzz =~ s/z/a/g;
            substr($name, length($name)-length($zzz)) = $zzz;
            if ( length($zzz) < length($name) ) {
                my $chr = substr($name, length($name)-length($zzz)-1, 1);
                substr($name, length($name)-length($zzz)-1, 1) = chr(ord($chr) + 1);
            } else {
                $name .= 'a';
            }
        } else {
            my $chr = substr($name, length($name)-1, 1);
            substr($name, length($name)-1) = chr(ord($chr) + 1);
        }
    }
    param("${name}_File", $rrdfile);
    if ( $rrdfile eq '' ) {
        $DS{$name} = '';
    } elsif ( $rrdfile =~ /^(.+)\/\/(.+).(rrd|evt)$/ ) {
        $DS{$name} = $datadirs{$1} . " " . $2;
    } else {
        die "Bad filename index in DSNew: $rrdfile\n";
    }
    param("${name}_DS", $DSname);
    param("${name}_RRA", $CFname);
    param("${name}_Seq", scalar(keys(%DS))+1);
    param("${name}_Label", "$DSname $CFname");
    param("${name}_Width", '');
    param("${name}_CDEF", '');
    param("${name}_Type", 'LINE1');
    param("${name}_STACK", 0);
    param("${name}_Color", (sort Sort_Colors keys(%colors))
          [(((param("${name}_Seq") -1) * 6) % scalar(keys(%colors)))
           + ((param("${name}_Seq") -1) / (scalar(keys(%colors)) / 6))
            + 1]);
    param("${name}_XTRAS", ());
    param("${name}_BR", 0);
    return $name;
}

sub ExpandMatches
{
    croak 'ExpandMatches($string, @array)' if ( scalar(@_) != 2 );
    my ( $str, $arr ) = ( @_ );

    my $i = 0;
    while ( $i < scalar(@$arr) ) {
        $i += 1;
        $str =~ s/\$$i(\D|$)/$$arr[$i-1]$1/g;
    }
    return $str;
}

sub ReduceMatches
{
    croak 'ReduceMatches($string, @array)' if ( scalar(@_) != 2 );
    my ( $str, $arr ) = ( @_ );

    my $i = 0;
    while ( $i < scalar(@$arr) ) {
        $i += 1;
        $str =~ s/$$arr[$i-1]/\$$i/g;
    }
    return $str;
}

sub time2str
{
    croak 'time2str([time])' if ( scalar(@_) > 1 );

    my $time = shift;
    $time = time unless ( defined($time) );

    my @weekday = ( 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');
    my @month =  ( 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug',
                   'Sep', 'Oct', 'Nov', 'Dec' );

    my ( $sec, $min, $hour, $mday, $mon, $year, $wday) = gmtime($time);
    return sprintf("%s, %02d %s %0d %02d:%02d:%02d GMT", $weekday[$wday],
                   $mday, $month[$mon], $year + 1900, $hour, $min, $sec);
}

sub IsNum
{
    croak 'IsNum($str)' if ( scalar(@_) != 1 );

    return ( $_[0] =~ /^-?\d+\.?\d*$/ );
}

sub Error
{
    croak 'Error(text)' if ( scalar(@_) != 1 );

    if ( defined(param('Mode')) && param('Mode') eq 'show' ) {
        carp 'Error while producing image: Mode=show, ' .
            ( ( defined(param('Graph')) ) ? 'Graph=' . param('Graph')
              : 'Template=' . param('Template') )
            . ': ' . $_[0];
    } else {
        print
            p({-class=>'error', -align=>'center'}, $_[0]);
    }
}

#
# Help
#

sub HELP
{
    my @toc = ( [ 'overview', 'Overview', &help_overview ],
                [ 'terms', 'Terminology', &help_terms ],
                [ 'gfiles', 'Graphs', &help_graphfiles ],
                [ 'dsconfig', 'DS Configuration', &help_dsconfig ],
                [ 'templates', 'Graph Templates', &help_templates ],
                [ 'dbintro', 'Dashboard Overview', &help_dbintro ],
                [ 'dbconfig', 'Dashboard Configuration', &help_dbconfig ],
                [ 'links', 'drraw URLs', &help_urls ],
                [ 'contact', 'Contact information', &help_contact ],
                [ 'version', 'Version information', &help_version ] );
    print h2('Table Of Contents'), start_ul;
    my $i = 0;
    while ($i < scalar(@toc) ) {
        print li(a({-href=>url(-path_info=>1, -query=>1, -relative=>1)
                        .'#'. $toc[$i][0]}, $toc[$i][1]));
        $i += 1;
    }
    print end_ul, hr;
    $i = 0;
    while ($i < scalar(@toc) ) {
        print a({-name=>$toc[$i][0]}, h2($toc[$i][1])), $toc[$i][2];
        $i += 1;
    }
    $footer,
    print end_html;
}

sub help_overview
{
    return
        p(em('drraw'), 'is a simple web based presentation front-end for ', a({-href=>'http://www.rrdtool.org/'}, 'RRDtool'), ' that allows you to interactively build graphs of your own design.  The following pages provide help on how to use ', em('drraw'), ' but no help on how to use ', a({-href=>'http://www.rrdtool.org/'}, 'RRDtool'), '.  Indeed, such knowledge is a basic requirement to using ', em('drraw'), ' and making the most of out it and the following documentation.  It is however interesting to note that it takes very little knowledge to be able to "play" with ', em('drraw'), ' as it provides a convenient interface to experiment and learn how to create graphs based on RRD files.');
}

sub help_terms
{
    return
        p('The following terms are used throughout this document and ', em('drraw'), '\'s edition pages.  Understanding what they mean is critical to understanding how to use ', em('drraw'),
          ul(
             li(b('RE'), ': Short for ', a({-href=>'http://www.perldoc.com/perl5.6/pod/perlre.html'}, '(perl) <b>R</b>egular <b>E</b>xpression')),
             li(b('Database / DB'), ': RRD database file'),
             li(b('Event File'), ': Event file'),
             li(b('CDEF'), ': Virtual Data Source (based on a mathematical expression)'),
             li(b('DS'), ': Data Source'),
             li(b('Graph'), ': Graphic representation of Data Sources from DB and event files'),
             li(b('Template'), ': Graph definition with variable Data Sources'),
             li(b('Dashboard'), ': Collection of graph previews'),
             ));
}

sub help_graphfiles
{
    return
        p('The first step in creating a graph is to choose which data to use.  This is done by selecting Data Sources from available databases and event files.', b('Only one type of Data Source (either database or event file) may be added at the same time.'));
}

sub help_dsconfig
{
    return
        p('Data Source configuration is contained into a single table which allows configuring graph elements from these Data Sources.  The basic format is for each row to allow configuring a single Data Source and/or CDEF, although various elements are displayed slightly differently.  It is important to understand and realize that ', em('drraw'), ' does not fully check what you specify to be valid.  It does, however, try to steer you away from simple mistakes, but ultimately, only ', a({-href=>'http://www.rrdtool.org/'}, 'RRDtool'), ' fully checks the syntax of your settings.  The table columns are as follows:',
          ul(li(b('DEL?'), ': Check to remove this line'),
             li(b('Name'), ': Data Source virtual name.  This is the name that you can reference in CDEFs.'),
             li(b('Seq'), ': Sequence'),
             li(b('Data Source'), ': Configuration depends on the type of Data Source being configured:',
                ul(li(b('Regular Data Source'), ': this is the RRD File and Data Source defined'),
                   li(b('HRULE'), ', ', b('VRULE'), ' graph elements as well as "pure" ', b('CDEF'), ' or ', b('VDEF'), ': this is blank'),
                   li(b('Event Data Sources'), ': this is the Event File.  These files contain one event per line, prefixed by the event timestamp (the number of non-leap seconds since whatever time the system considers to be the epoch) followed by a space.  These events are automatically added to the graph as ', b('VRULE'), ' elements.'),
                   li(b('Data Source Template'), ': this defines how Data Sources will be automatically generated by ', em('drraw'), ':', br, 'For each RRD File matching the "File RE", a Data Source will be created for the specified Data Source name.',
                      ul(li('The second row allows defining how each created Data Source will be displayed as if it was a Regular Data Sources.'),
                         li('The "Label" entry may contain references ($1, $2, ..) to subexpressions defined by the "File RE".'),
                         li('All these Data Sources may be combined into a composite Data Source by using the "Element" and "Formula" fields.  The former is applied to each Data Source individually, and the latter used to combine them together.  For example, to get the sum of all Data Sources, use "+" as "Formula".  In addition, if you want to make sure you get a real value for the combined Data Source and not NaN whenever one of the element values is NaN, you may use "UN,0,$,IF" in the "Element" field.'),
                         li('You may also use "AVERAGE" or "STDDEV" in the "Formula" field to have ', em('drraw'), ' calculate the average or the standard deviation (respectively).'),
                         li('The character "#" will be replaced by the count of Data Sources defined by the "File RE".')))),
                'Checking the "Lists" option allows changing the Data Source file for Regular and Event Data Sources'),
             li(b('RRA'), ': RRA selection for Data Sources, or ', b('CDEF'), '/', b('VDEF'), ' choice'),
             li(b('CDEF'), ': If defined, this will be used to create a ', b('CDEF'), ' that will be used in place of the Data Source.  The ', b('CDEF'), ' virtual name is the Data Source virtual name in uppercase (see below).  The character "$" will automatically be replaced by the Data Source virtual name.  This is also used as the formula for "pure"', b('CDEF'), ' and ', b('VDEF'), ' definitions, as well as the value for ', b('HRULE'), ' and ', b('VRULE'), ' graph elements.'),
             li(b('Type'), ': Graph element type'),
             li(b('Color'), ': Graph element color (if applicable)'),
             li(b('Label / Format'), ': Legend label, or format.  For convenience, the first label of each line (in the legend area) is padded with spaces so that all such labels are of the same length.  No padding is done if the label contains a % or \\ character.'),
             li(b('Width'), ': Width for ', b('TICK'), ' and ', b('LINE?'), ' graph elements.  (', a({-href=>'http://www.rrdtool.org/'}, 'RRDtool'), ' 1.2 and above only)'),
             li(b('Additional GPRINTs'), ': For each defined RRA, additional GPRINTs may be automatically generated for the Data Source (or CDEF if it is defined) and added to the legend.'),
             li(b('BR'), ': Whether or not to add a line break (in the legend area) after this element'))).
        p(b('Note'), ':  If you are using ', a({-href=>'http://www.rrdtool.org/'}, 'RRDtool'), ' 1.2 or above, ', em('drraw'), ' automatically enables new features available with it, and silently disables deprecated features.  However, deprecated features used in previously saved graphs are ', b('not'), ' automatically updated if you edit the graph and save it again.  When possible, deprecated features in use in previously saved graphs are nonetheless converted when running ', a({-href=>'http://www.rrdtool.org/'}, 'RRDtool'));
}

sub help_templates
{
    return
        p('A template is simply a graph definition for which the database files are variable.  In other words, a template is defined by selecting database files: ',
          ul(li(b('Base Regular Expression'), ': This (perl) regular expression will be applied to the list of database files.  This expression must have at least one subexpression. (see below)'),
             li(b('Selection Expression'), ': If defined, this expression should contain references ($1, $2, ..) to subexpressions defined in the base regular expression.  The strings resulting from expanding these will be displayed to users as a list for choosing which graph to view.'),
             li(b('Database File Names'), ': Each database file used by the graph definition must have a name that can be derived from the result of the base regular expression.  As for the selection expression, this should be done by using references ($1, $2, ..) to subexpressions defined by the base regular expression.')),
          'Note that ', em('drraw'), ' will make a simple attempt to generate the database filename strings automatically, so you should click "Update" after defining the base expression.', b('Any database filename string for which the result does not match the graph definition filename will appear in red')) .
        p('For example, if you have a graph using the "host1/Interfaces/int1.rrd", you may want to set the "Base Regular Expression" to be: "(.+)/Interfaces/(.+)\.rrd".  The "Database File Name" will be replaced by "$1/Interfaces/$2.rrd".  Unless you define a "Selection Expression", one of the choices you will be given as selection for the template is "host1 - int1".');
}

sub help_dbintro
{
    return
        p('A dashboard is a collection of graph previews.  Defining a dashboard is a simple matter of selecting which graphs and templates will appear on it.  There are two basic types of boards:',
          ul(li(b('Standard'), ': These have a set (by configuration) number of columns, and may mix graphs with templates.'),
             li(b('Grouped'), ': This special type may only be used with templates.  The number of columns is determined by the number of items defined by the configuration.  Rows are made up by the targets for which the templates are previewed.')));
}
    
sub help_dbconfig
{
    return
        p('Dashboard configuration is done by choosing the previews that will appear on the dashboard.  Each line configures a graph or template preview.  For templates, there are several ways to configure which available selections are actually displayed in the dashboard:',
          ul(li(b('List'), ': The list of targets is manually configured/selected.'),
             li(b('Regex'), ': The list of targets is defined by a (perl) regular expression'),
             li(b('All'), ': Display all available targets'),
             li(b('Base'), ': One target only, user selected'))) .
        p('With ', b('"Standard" dashboards'), ' it is possible to define a "', b('dashboard template'), '" based on existing (graph) templates by using the "Base" display style (see above).  A (perl) regular expression may be entered in the "Template Base" column to (optionally) restrict the choices available for the already defined template.  The "Selection Regular Expression" column should be a (perl) regular expression to be applied to the (graph) template selections, it must cause subpatterns to be defined.  These will be presented to the user for selection.') .
        p('With ', b('"Grouped" dashboards'), ', the "Row Name" column defines in which row targets will be displayed.  By default (if undefined), targets are placed in a row of the same name.  If defined, the "Row Name" must be a (perl) regular expression that causes subpatterns to be defined which will be used as row name.  These names are never shown to the user.') .
        p('The following two notes are important to keep in mind when configuring template previews:',
          ul(li('The "Selection" drop-down list shown for each chosen template is not actually used by ', em('drraw'), 'but is presented to you as a help to write regular expressions.'),
             li('Unlike template configuration regular expressions, all regular expressions defined here will be applied to the values listed in the "Selection" drop-down list shown for the template.')),
          'Finally, it is important to understand that the "Graph / Template" selections are stored using an internal ID rather than the actual title.  If the selected graph or template is later deleted, its name will be lost and the next time the dashboard is edited, this row will be automatically marked for deletion and displayed in red.');
}

sub help_urls
{
    return
        h4('drraw URLs') .
        p('The URLs used by ', em('drraw'), ' to view defined graphs, templates and dashboards are fairly short, and as such, suitable for use in e-mail or other web pages.  The internal ID used to reference a graph, template or dashboard is generated upon creation (or cloning) and will remain the same for a given item.  It is interesting to note that the following options may be applied to actual graph (e.g. image) URLs to override settings saved:',
          ul(li('Start'), li('End'), li('Width'), li('Height'),
             li('NoLegend'), li('Format')));
}

sub help_contact
{
    return
        h4('Mailing lists') .
        p('If you want to be informed about important news about ', em('drraw'), ', such as new releases, subscribe to the drraw-announce mailing list, either by sending a mail including the "subscribe" keyword to ', a({-href=>'mailto:drraw-announce-request@taranis.org?body=subscribe'}, 'drraw-announce-request@taranis.org'), 'or simply visit the following web page: ', a({-href=>'http://web.taranis.org/mailman/listinfo/drraw-announce'}, 'http://web.taranis.org/mailman/listinfo/drraw-announce'), '.') .
        p('For other discussions or if you have questions about ', em('drraw'), ', subscribe to the drraw-users mailing list, either by sending a mail including the "subscribe" keyword to ', a({-href=>'mailto:drraw-users-request@taranis.org?body=subscribe'}, 'drraw-users-request@taranis.org'), 'or simply visit the following web page: ', a({-href=>'http://web.taranis.org/mailman/listinfo/drraw-users'}, 'http://web.taranis.org/mailman/listinfo/drraw-users'), '.  You must be a subscriber to post on this list.') .
        h4('Wiki') .
        p('Additional documentation can be found on ', a({-href=>'http://web.taranis.org/drraw/wiki'}, em('drraw'), ' Wiki'), '.') .
        h4('Bugs') .
        p('Send bug reports to ', a({-href=>"mailto:drraw-bugs\@taranis.org?subject=drraw bug report&body=drraw version: $VERSION, Perl $], CGI $CGI::VERSION, RRD $RRDs::VERSION on $^O with ". $ENV{'SERVER_SOFTWARE'}}, 'drraw-bugs@taranis.org'), '.  A bug report is an adequate description of the environment (versions of this package, the OS, Perl, the CGI module and RRDtool) and of the problem: your input, what you expected, what you got, and why you believe it to be wrong.  Diffs are welcome, but they only describe a solution, from which the problem might be difficult to infer.') .
        h4('Others') .
        p('Your feedback will help to make a better and more portable package.
Consider documentation errors as bugs, and report them as such.  If you develop anything pertaining to ', em('drraw'), ' or have suggestions, share them by writing to ', a({-href=>'mailto:drraw@taranis.org'}, 'drraw@taranis.org'), '.');
}

sub help_version
{
    return table({-align=>'center', -cellpadding=>'5%', -border=>2},
                 Tr(td({-align=>'right', -width=>'50%'}, b('drraw.cgi')),
                    td({-align=>'left', -width=>'50%'},
                       $VERSION .' <'. $REVISION .'>')),
                 Tr(td({-align=>'right', -width=>'50%'}, b('Perl')),
                    td({-align=>'left', -width=>'50%'}, "$]")),
                 Tr(td({-align=>'right', -width=>'50%'}, b('CGI.pm')),
                    td({-align=>'left', -width=>'50%'}, "$CGI::VERSION")),
                 Tr(td({-align=>'right', -width=>'50%'}, b('RRDs.pm')),
                    td({-align=>'left', -width=>'50%'}, "$RRDs::VERSION")));
}

# Local Variables:
# mode: perl
# indent-tabs-mode: nil
# tab-width: 4
# cperl-indent-level: 4
# End:
