<?
namespace fmcore;

class Text
{
	/**
	 * @deprecated
	 * @param $text
	 * @return mixed
	 */
	public static function correctLinks($text)
	{
		// Looking for links
		$text = preg_replace("/<a.*?(href=\".*?\").*?>/i", '<a $1 rel="nofollow" target="_blank">', $text);

		// Looking for forms (donation form etc)
		$text = preg_replace("/<form(.*?)(target=\".*?\")(.*?)>/i", '<form $1 $3>', $text); // remove any target
		$text = preg_replace("/<form(.*?)>/i", '<form $1 target="_blank">', $text);// add target

		return $text;
	}

	/**
	 * @param $txt
	 * @param bool $correctLinks
	 * @return mixed|string
	 */
	public static function sanitize($txt, $correctLinks=false)
	{
		$cfgSchema = \HTMLPurifier_ConfigSchema::instance( \HTMLPurifier_ConfigSchema::makeFromSerial() );

		$config = new \HTMLPurifier_Config($cfgSchema);

		$tags = [
			'pre',
			'blockquote',
			'p',
			'strong','b',
			'em',
			'i',
			'u',
			's','del', // stroken
			'span',
			'a[href|title]',
			'br',
			'div',
			'h4','h5','h6',
			'ul','ol','li',

			'img[src|title|alt|width|height|border]',

			'*[style]',
			'*[title]',

			'form[action|method|target]',
			'input[type|name|value]',
			'input[src|alt]', // paypal submit button
//          'input[src^=https://www.paypalobjects.com/
//			'form[action^=https://www.paypal.com/]',
		];

		$config->set('HTML.Allowed', implode(',',$tags));
		$config->set('Output.SortAttr', true); // reorder attributes

		$config->set('Attr.AllowedFrameTargets', array('_blank'));

//		$config->set('AutoFormat.AutoParagraph', true);
		$config->set('AutoFormat.Linkify', true);
		$config->set('AutoFormat.RemoveEmpty', true);
		$config->set('AutoFormat.RemoveSpansWithoutAttributes', true);

		$config->set('HTML.SafeIframe', true);
		$config->set('URI.SafeIframeRegexp', '%^https?://(www.youtube(?:-nocookie)?.com/embed/|player.vimeo.com/video/)%');
		$config->set('HTML.Trusted', true);

//		$config->set('HTML.TidyAdd', true); //error: Value for HTML.TidyAdd is of invalid type, should be lookup invoked
//		$config->set('HTML.TidyLevel', 'heavy');

		$config->set('Core.EscapeInvalidTags', true);

//TODO make url Munge
//		$config->set('URI.Base', 'http://example.com/');
//		$config->set('URI.Munge', 'http://example.com/away?url=%s');
		$config->set('URI.AllowedSchemes', 'http,https,mailto,ftp');

		if($correctLinks)
		{
			$config->set('HTML.Nofollow', true);
			$config->set('HTML.TargetBlank', true);

			if(is_debug())
			{
				$config->set('Cache.DefinitionImpl', null);// remove this later!, this is cache
			}
			$config->set('HTML.DefinitionID', 'form-target-blank');
			$config->set('HTML.DefinitionRev', preg_replace("/[^0-9]/", "", FM_VERSION));
			$config->set('URI.DefinitionID', 'form-action-regexp');
			$config->set('URI.DefinitionRev', preg_replace("/[^0-9]/", "", FM_VERSION));

			if($def=$config->maybeGetRawHTMLDefinition())
			{
				/**
				 * @var $def \HTMLPurifier_Definition|\HTMLPurifier_HTMLDefinition
				 */
				$formHandler                        =$def->addBlankElement('form');
				$formHandler->attr_transform_post[ ]=new \HTMLPurifier_FormAttrTransform_TargetBlank();
			}

			if($def=$config->maybeGetRawURIDefinition())
			{
				/**
				 * @var $def \HTMLPurifier_Definition|\HTMLPurifier_URIDefinition
				 */
				$def->registerFilter(new \HTMLPurifier_URIFilter_FormActionRegexp("/^https?:\/\/(www\.|)paypal\.com\//i"));
			}
		}

		$txt = \HTMLPurifier::getInstance($config)->purify($txt);

//@deprecated
//		$txt = strip_tags($txt, '<blockquote><p><strong><b><em><i><u><span><a><style><br><div><h4><h5><h6><ul><ol><li><form><img>');

		$txt = self::clearSpare($txt);

		return $txt;
	}

	/**
	 * @param $txt
	 * @return string
	 */
	public static function trimHtml($txt)
	{
		//@see http://stackoverflow.com/questions/4482152/problem-using-strip-tags-in-php
		$txt = preg_replace('/(<\/[^>]+?>)(<[^>\/][^>]*?>)/', '$1 $2', $txt);
		$txt = strip_tags($txt);
		$txt = str_replace("&nbsp;", " ", $txt);
		$txt = self::clearSpare($txt);

		return $txt;
	}

	/**
	 * @param $txt
	 * @return mixed|string
	 */
	public static function clearSpare($txt)
	{
		$txt = preg_replace("/[\n\r]+/", "\n", $txt);
		$txt = trim($txt, "\r\n\t ");

		return $txt;
	}


	/**
	 * @param $text
	 * @return mixed
	 */
	public static function toHtml($text)
	{
		$str = htmlspecialchars($text);
		$str = str_replace("\r\n", "<br />", $str);
		$str = str_replace(array("\r","\n"), "<br />", $str);
		//$str = str_replace('  ', '&nbsp;&nbsp;', $str); ///a lot of spaces should be rendered exactly
		//@follow http://stackoverflow.com/a/13992998 , "\xC2\xA0" - NO-BREAK SPACE, same as &nbsp;
		$str = str_replace('  ', "\xC2\xA0", $str); ///a lot of spaces should be rendered exactly
		return $str;
	}

	/**
	 * TODO make smarter - do not cut words etc.
	 * @param $string
	 * @param $maxLength
	 * @return string
	 */
	public static function limit($string, $maxLength)
	{
		return substr($string,0,$maxLength);
	}
}
