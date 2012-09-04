<?php
/**
 * NAME
 *
 *      <include/>
 *
 * SYNOPSIS
 *
 *      <include src="[URL]" [noesc] [nopre] [svncat] [highlight="[URL]"]
 *      [csshref="[URL]"] />
 *
 * INSTALL
 *
 *      Put this script on your server in your MediaWiki extensions directory:
 *
 *          "$IP/extensions/include.php"
 *
 *      where $IP is the Install Path of your MediaWiki. Then add these lines to LocalSettings.php:
 *
 *          require_once("$IP/extensions/include.php");
 *          $wg_include_allowed_parent_paths = $_SERVER['DOCUMENT_ROOT'];
 *          $wg_include_disallowed_regex = array('/.*LocalSettings.php/', '/.*\.conf/');
 *
 *     Note that these settings allow any document under your DOCUMENT_ROOT to be shared
 *     except LocalSettings.php or any file ending in .conf. You can add other regex patterns
 *     for files that you want to disallow. You can also set $wg_include_allowed_parent_paths
 *     as an array of allowed paths:
 *
 *         $wg_include_allowed_parent_paths = array($_SERVER['DOCUMENT_ROOT'], '/home');
 *
 *     These settings affect local and remote URLs. These do not affect SVN URLs.
 *
 * DESCRIPTION
 *
 *     This extension allows you to include the contents of remote and local
 *     files in a wiki article. It can optionally include content in an iframe.
 *
 *     This extension should almost certainly make you concerned about security!
 *     See the INSTALL section. The $wg_include_allowed_parent_paths and
 *     $wg_include_disallowed_regex configuration settings in LocalSettings.php
 *     can help limit access.
 *
 *     Note that external content is only refreshed
 *     when you save the wiki page that contains the <include/>. Changing the
 *     external file WILL NOT update the wiki page until the wiki page is
 *     edited and saved (not merely refreshed in the browser).
 *     You can also instruct the server to refresh the page by adding the
 *     refresh action. See
 *     http://en.wikipedia.org/wiki/Wikipedia:Bypass_your_cache#Server_cache
 *     You can add the following to a wiki page to make it easier to
 *     clear the cache:
 *     <code>{{fullurl:{{NAMESPACE}}:{{PAGENAME}}|action=purge}}</code>
 *
 *     For the latest version go here:
 *         http://www.noah.org/wiki/MediaWiki_Include
 *
 * ATTRIBUTES
 *
 *      The <include/> tag must always include at least have a 'src' attribute.
 *
 *      src="[URL]"
 *          You must include 'src' to specify the URL of the file to import.
 *          This may be the URL to a remote file or it may be a
 *          local file system path.
 *
 *      iframe
 *          This sets tells the extension to render the included file
 *          as an iframe.  If the iframe attribute is included then the
 *          following attributes may also be included to determine how
 *          the iframe is rendered:
 *
 *              width
 *              height
 *
 *          Exampple:
 *
 *              <include iframe src="http://www.noah.org/cgi-bin/pr0n" width="" height="1000px" />
 *
 *      noesc
 *          By default <include> will escape all HTML entities in
 *          the included text. You may turn this off by adding
 *          the 'noesc' attribute. It does not take any value.
 *
 *      nopre
 *          By default <include> will add <pre></pre> tags around
 *          the included text. You may turn this off by adding
 *          the 'nopre' attribute. It does not take any value.
 *
 *      wikitext
 *          This treats the included text as Wikitext. The text is
 *          passed to the Mediawiki parser to be turned into HTML.
 *
 *      svncat
 *          This is used for including files from SVN repositories.
 *          This will tell include to use "svn cat" to read the file.
 *          The src URL is passed directly to svn, so it can be any
 *          URL that SVN understands.
 *
 *      linenums
 *          This will add line numbers to the beginning of each line
 *          of the inluded text file.
 *
 *      highlight="[SYNTAX]"
 *          You may colorize the text of any file that you import.
 *          The value of SYNTAX must be one of the following:
 *
 *              CPP
 *              CSS
 *              diff
 *              DTD
 *              HTML
 *              Java
 *              Javascript
 *              MySQL
 *              Perl
 *              PHP
 *              Python
 *              Ruby
 *              SQL
 *              XML
 *
 *      csshref="[URL]"
 *          If you set 'highlight' then by default it includes an inline CSS.
 *          You may override this and link to the style sheet of your choice.
 *          Set "URL" to point to the CSS you want to use.
 *
 * EXAMPLES
 *
 *      Include a file from the local file system:
 *          <include src="/var/www/htdocs/README" />
 *      Include a remote file:
 *          <include src="http://www.google.com/search?q=noah.org" nopre noesc />
 *      Include a local fragment of HTML:
 *          <include src="/var/www/htdocs/header.html" nopre noesc />
 *      Include a local file with syntax highlighting:
 *          <include src="/home/svn/checkout/trunk/include.php" highlight="php" />
 *
 * DEPENDENCIES
 *
 *      For highlight support you will need to install PEAR Text_Highlighter.
 *      For example:
 *          pear install --alldeps
 *          http://download.pear.php.net/package/Text_Highlighter-0.6.9.tgz
 *
 * AUTHOR
 *
 *      Noah Spurrier <noah@noah.org>
 *      http://www.noah.org/wiki/MediaWiki_Include
 *
 * @package extensions
 * @version 8
 * @copyright Copyright 2008 @author Noah Spurrier
 * @license public domain -- free of any licenses and restrictions
 *
 * $Id: include.php 256 2008-05-20 17:01:29Z noah $
 * vi:ts=4:sw=4:expandtab:ft=php:
 */
 
// We want highlighting, but it isn't required, so set a flag to show if it's available or not.
@include 'Text/Highlighter.php';
if (class_exists('Text_Highlighter'))
{
    $highlighter_package = True;
}
else
{
    $highlighter_package = False;
}
 
$wgExtensionFunctions[] = "wf_include";
$wgExtensionCredits['other'][] = array
(
    'name' => 'include',
    'author' => 'Noah Spurrier',
    'url' => 'http://mediawiki.org/wiki/Extension:include',
    'description' => 'This lets you include static content from the local file system; a remote URL; or SVN.',
);
 
function wf_include()
{
    global $wgParser;
    $wgParser->setHook( "include", "render_include" );
}
 
$inline_css='<style type="text/css">
.hl-default {color:Black;}
.hl-code {color:Gray;}
.hl-brackets {color:Olive;}
.hl-comment {color:Orange;}
.hl-quotes {color:Darkred;}
.hl-string {color:Red;}
.hl-identifier {color:Blue;}
.hl-builtin {color:Teal;}
.hl-reserved {color:Green;}
.hl-inlinedoc {color:Blue;}
.hl-var {color:Darkblue;}
.hl-url {color:Blue;}
.hl-special {color:Navy;}
.hl-number {color:Maroon;}
.hl-inlinetags {color:Blue;}
.hl-main {background-color:White;}
.hl-gutter {background-color:#999999; color:White}
.hl-table {font-family:courier; font-size:12px; border:solid 1px Lightgrey;}
</style>
';
 
/**
 * path_in_regex_disallowed_list
 * This returns true if the needle_path matches any regular expression in haystack_list.
 * This returns false if the needle_path does not match any regular expression in haystack_list.
 * This returns false if the haystack_list is not set or contains no elements.
 *
 * @param mixed $haystack_list
 * @param mixed $needle_path
 * @access public
 * @return boolean
 */
function path_in_regex_disallowed_list ($haystack_list, $needle_path)
{
    if ( ! isset($haystack_list) || count($haystack_list) == 0)
    {
        return false;
    }
    // polymorphism. Allow either a string or an Array of strings to be passed.
    if (is_string($haystack_list))
    {
        $haystack_list = Array($haystack_list);
    }
    foreach ($haystack_list as $p)
    {
        if (preg_match ($p, $needle_path))
        {
            return true;
        }
    }
    return false;
}
 
/**
 * path_in_allowed_list
 * This returns true if the given needle_path is a subdirectory of any directory listed in haystack_list.
 *
 * @param mixed $haystack_list
 * @param mixed $needle_path
 * @access public
 * @return boolean
 */
function path_in_allowed_list ($haystack_list, $needle_path)
{
    if ( ! isset($haystack_list) || count($haystack_list) == 0)
    {
        return false;
    }
    // polymorphism. Allow either a string or an Array of strings to be passed.
    if (is_string($haystack_list))
    {
        $haystack_list= Array($haystack_list);
    }
    foreach ($haystack_list as $path)
    {
        if (strstr($needle_path, $path))
        {
            return true;
        }
    }
    return false;
}
 
/**
 * render_include
 *
 * This is called automatically by the MediaWiki parser extension system.
 * This does the work of loading a file and returning the text content.
 * $argv is an associative array of arguments passed in the <include> tag as
 * attributes.
 *
 * @param mixed $input unused
 * @param mixed $argv associative array
 * @param mixed $parser unused
 * @access public
 * @return string
 */
function render_include ( $input , $argv, $parser )
{
    global $inline_css, $highlighter_package;
    global $wg_include_allowed_parent_paths, $wg_include_disallowed_regex;
 
    $error_msg_prefix = "ERROR in " . basename(__FILE__);
 
    if ( ! isset($argv['src']))
        return $error_msg_prefix . ": <include> tag is missing 'src' attribute.";
 
    // You can add this to restrict contents to a given path or DOCUMENT_ROOT:
    //    if (is_file(realpath($argv['src'])) && strlen(strstr(realpath($argv['src']), realpath($_SERVER['DOCUMENT_ROOT']))) <= 0)
    //        return $error_msg_prefix . ": src to local path is not under DOCUMENT_ROOT.";
    // Or you could use this to disallow local files altogether:
    //    if (is_file(realpath($argv['src'])))
    //        return $error_msg_prefix . ": Local files are not allowed.";
    // You can use similar tricks to restrict the src url.
 
    // iframe option...
    // Note that this does not check that the iframe src actually exists.
    // I also don't need to check against $wg_include_allowed_parent_paths or $wg_include_disallowed_regex
    // because the iframe content is loaded by the web browser and so security
    // is handled by whatever server is hosting the src file.
    if (isset($argv['iframe']))
    {
        if (isset($argv['width']))
            $width = $argv['width'];
        else
            $width = '100%';
        if (isset($argv['height']))
            $height = $argv['height'];
        else
            $height = '100%';
        if (isset($argv['frameborder']))
            $frameborder = $argv['frameborder'];
        else
            $frameborder = '1';
  
        $output = '<iframe src="'.
            $argv['src'].
            '" frameborder="'.
            $frameborder .
            '" scrolling="1" width="'.
            $width .
            '" height="'.
            $height .
            '">iframe</iframe>';
        return $output;
    }
 
    // cat file from SVN repository...
    if (isset($argv['svncat']))
    {
        $cmd = "svn cat " . escapeshellarg($argv['src']);
        exec ($cmd, $output, $return_var);
        // If plain 'svn cat' fails then try again using 'svn cat
        // --config-dir=/tmp'. Plain 'svn cat' worked fine for months
        // then just stopped.
        // Adding --config-dir=/tmp is a hack that fixed it, but
        // I only want to use it if necessary. I wish I knew what
        // the root cause was.
        if ($return_var != 0)
        {
            $cmd = "svn cat --config-dir=/tmp " . escapeshellarg($argv['src']);
            exec ($cmd, $output, $return_var);
        }
        if ($return_var != 0)
            return $error_msg_prefix . ": could not read the given src URL using 'svn cat'.\ncmd: $cmd\nreturn code: $return_var\noutput: " . join("\n", $output);
        $output = join("\n", $output);
    }
    else // load file from URL (may be a local or remote URL)...
    {
        $src_path = realpath($argv['src']);
        if ( ! $src_path )
        {
            $src_path = $argv['src'];
        }
        else
        {
            if ( ! path_in_allowed_list ($wg_include_allowed_parent_paths, $src_path))
            {
                return $error_msg_prefix . ": src_path is not a child of any path in \$wg_include_allowed_parent_paths.";
            }
            if ( path_in_regex_disallowed_list ($wg_include_disallowed_regex, $src_path) )
            {
                return $error_msg_prefix . ": src_path matches a pattern in \$wg_include_disallowed_regex.";
            }
        }
        $output=file_get_contents($argv['src']);
        if ($output === False)
            return $error_msg_prefix . ": could not read the given src URL.";
    }
 
    // FIXME line numbers should be added after highlighting.
    if (isset($argv['linenums']))
    {
        $output_a = split("\n",$output);
        for ($index = 0; $index < count($output_a); ++$index)
            $output_a[$index] = sprintf("%04d",1+$index).":".$output_a[$index];
        $output = join("\n", $output_a);
    }
 
    if (isset($argv['highlight']) && $highlighter_package===True)
    {
//        if ($highlighter_package === False)
//        {
//            return $error_msg_prefix . ": Text_Highlighter is not installed. You can't use 'highlight'.";
//        }
 
        $hl =& Text_Highlighter::factory($argv['highlight']);
        $output = $hl->highlight($output);
        if (isset($argv['csshref']))
        {
            $output = '<link rel="stylesheet" href="' . $argv['csshref'] . '" type="text/css" media="all" />' . "\n". $output;
        }
        else
        {
            $output = $inline_css . $output;
        }
        return $output;
    }
 
    if ( ! isset($argv['noesc']))
        $output = htmlentities( $output );
 
    if (isset($argv['wikitext']))
    {
        $parsedText = $parser->parse($output,
                      $parser->mTitle,
                      $parser->mOptions,
                      false,
                      false);
        $output = $parsedText->getText();
    }
    else
    {
        if ( ! isset($argv['nopre']))
            $output = "<pre>" . $output . "</pre>";
    }
    return $output;
}
?>
