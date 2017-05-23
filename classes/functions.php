<?php
class Functions
{
	/*
		<summary>
		generate_uniqueID($length)
		Genereerd een string met een bepaalde lengte

		$length: lengte van de unique string
		</summary>
	*/
	public function generate_uniqueID($length)
	{
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$randomString = '';

		for ($i = 0; $i < $length; $i++)
		{
			$randomString .= $characters[rand(0, strlen($characters) - 1)];
		}
		return $randomString;
	}

	public function bb_parse($string) {
        $tags = 'b|i|u|size|url|img|video|spoiler|color|hl';
        // center, quote, color not working yet
        // working on img

        /*
			case 'img':
                list($width, $height) = preg_split('`[Xx]`', $param);
                $replacement = "<img src=\"$innertext\" " . (is_numeric($width)? "width=\"$width\" " : '') . (is_numeric($height)? "height=\"$height\" " : '') . '/>';
            break;
        */

        while(preg_match_all('`\[('.$tags.')=?(.*?)\](.+?)\[/\1\]`', $string, $matches))
    	foreach ($matches[0] as $key => $match)
    	{
            list($tag, $param, $innertext) = array($matches[1][$key], $matches[2][$key], $matches[3][$key]);
            switch($tag)
            {
                case 'b': 		$replacement = '<strong>' . $innertext . '</strong>'; break;
                case 'i': 		$replacement = '<em>' . $innertext . '</em>'; break;
                case 'u': 		$replacement = '<u>' . $innertext . '</u>'; break;
                case 'size': 	$replacement = '<span style = "font-size:' . $param . 'px;">' . $innertext . '</span>'; break;
                case 'color': 	$replacement = '<span style = "color: ' . $param . '">' . $innertext . '</span>'; break;
                case 'center': 	$replacement = '<span style = "text-align: center;">' . $innertext . '</div>'; break;
                case 'quote': 	$replacement = '<blockquote>' . $innertext . '</blockquote>' . $param ? '<cite>' . $param . '</cite>' : ''; break;
                case 'url':
                    if($param)
                    {
                        if(strpos($param, 'http://') == false)
                        {
                            $param = 'http://' . $param;
                        }
                    }
                    else
                    {
                        if(strpos($innertext, 'http://') == false)
                        {
                            $innertext = 'http://' . $innertext;
                        }
                    }

                    $replacement = '<b><a class = "blue-text" href="' . ($param ? $param : $innertext) . '">' . $innertext . '</a></b>';
                break;

                case 'spoiler':
                    $sRandomString = Functions::generate_uniqueID(5);
                        $replacement = '<div class = "panel-header">
                                            <a class = "no-underline" data-toggle = "collapse" href = "#' . $sRandomString . '">
                                                <div class = "panel-heading panel-border">
                                                    <h4 class = "panel-title" align = "center">
                                                        <i>' . $param . '</i>
                                                    </h4>
                                                </div>
                                            </a>

                                            <div id = "' . $sRandomString . '" class = "panel-collapse collapse panel-text">
                                                <div class = "panel-body">
                                                    ' . $innertext . '
                                                </div>
                                            </div>
                                        </div>';
                break;

                case 'img':     $replacement = '<img src = "' . $innertext . '" />'; break;
                case 'video':
                    $videourl = parse_url($innertext);
                    parse_str($videourl['query'], $videoquery);
                    if(strpos($videourl['host'], 'youtube.com') !== FALSE) $replacement = '<embed src="http://www.youtube.com/v/' . $videoquery['v'] . '" type="application/x-shockwave-flash" width="425" height="344"></embed>';
                    if(strpos($videourl['host'], 'google.com') !== FALSE) $replacement = '<embed src="http://video.google.com/googleplayer.swf?docid=' . $videoquery['docid'] . '" width="400" height="326" type="application/x-shockwave-flash"></embed>';
                break;

                case 'hl':
                    $replacement = '<span style = "background-color: red; color: white;">' . $innertext . '</span>';
                break;
            }

            $string = str_replace($match, $replacement, $string);
        }
        return $string;
    }
}
