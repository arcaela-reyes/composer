<?php

namespace Arcaela;

if(!function_exists("create_closure")){
    /**
     * Create a Closure from string
     *
     * NOTICE: Do not input that passed from user request
     *
     * @see https://www.php.net/create_function
     * @param string $args
     * @param string $code
     * @return \Closure
     */
    function create_closure($args, $code) {
        return eval("return function ({$args}) { {$code} };");
    }
}
if(!function_exists("create_function")){
    /**
     * Create an anonymous (lambda-style) function, without error
     *
     * NOTICE: Do not input that passed from user request
     */
    function create_function($args, $code) {
        if (PHP_MAJOR_VERSION <= 7)
            return create_function($args, $code);
        static $i;
        $namespace = __NAMESPACE__;
        do {
            $i++;
            $name = "__{$namespace}_lambda_{$i}";
        } while (\function_exists($name));
        eval("function {$name}({$args}) { {$code} }");
        return $name;
    }
}
/**
 * ArcaelaDOM is a server-side, chainable, CSS3 selector driven
 * Document Object Model (DOM) API based on jQuery JavaScript Library.
 *
 * @version 0.9.5
 * @link http://jquery.com/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 * @package ArcaelaDOM
 */
define('DOMDOCUMENT', 'DOMDocument');
define('DOMELEMENT', 'DOMElement');
define('DOMNODELIST', 'DOMNodeList');
define('DOMNODE', 'DOMNode');
/**
 * DOMEvent class.
 *
 * Based on
 * @link http://developer.mozilla.org/En/DOM:event
 * @package ArcaelaDOM
 * @todo implement ArrayAccess ?
 */
class DOMEvent {
	/**
	 * Returns a boolean indicating whether the event bubbles up through the DOM or not.
	 *
	 * @var unknown_type
	 */
	public $bubbles = true;
	/**
	 * Returns a boolean indicating whether the event is cancelable.
	 *
	 * @var unknown_type
	 */
	public $cancelable = true;
	/**
	 * Returns a reference to the currently registered target for the event.
	 *
	 * @var unknown_type
	 */
	public $currentTarget;
	/**
	 * Returns detail about the event, depending on the type of event.
	 *
	 * @var unknown_type
	 * @link http://developer.mozilla.org/en/DOM/event.detail
	 */
	public $detail;
	/**
	 * Used to indicate which phase of the event flow is currently being evaluated.
	 *
	 * NOT IMPLEMENTED
	 *
	 * @var unknown_type
	 * @link http://developer.mozilla.org/en/DOM/event.eventPhase
	 */
	public $eventPhase;
	/**
	 * The explicit original target of the event (Mozilla-specific).
	 *
	 * NOT IMPLEMENTED
	 *
	 * @var unknown_type
	 */
	public $explicitOriginalTarget;
	/**
	 * The original target of the event, before any retargetings (Mozilla-specific).
	 *
	 * NOT IMPLEMENTED
	 *
	 * @var unknown_type
	 */
	public $originalTarget;
	/**
	 * Identifies a secondary target for the event.
	 *
	 * @var unknown_type
	 */
	public $relatedTarget;
	/**
	 * Returns a reference to the target to which the event was originally dispatched.
	 *
	 * @var unknown_type
	 */
	public $target;
	/**
	 * Returns the time that the event was created.
	 *
	 * @var unknown_type
	 */
	public $timeStamp;
	/**
	 * Returns the name of the event (case-insensitive).
	 */
	public $type;
	public $runDefault = true;
	public $data = null;
	public function __construct($data) {
		foreach($data as $k => $v) {
			$this->$k = $v;
		}
		if (! $this->timeStamp)
			$this->timeStamp = time();
	}
	/**
	 * Cancels the event (if it is cancelable).
	 *
	 */
	public function preventDefault() {
		$this->runDefault = false;
	}
	/**
	 * Stops the propagation of events further along in the DOM.
	 *
	 */
	public function stopPropagation() {
		$this->bubbles = false;
	}
}
/**
 * \DOMDocumentWrapper class simplifies work with \DOMDocument.
 *
 * Know bug:
 * - in XHTML fragments, <br /> changes to <br clear="none" />
 *
 * @todo check XML catalogs compatibility
 * @package ArcaelaDOM
 */
class DOMDocumentWrapper {
	/**
	 * @var \DOMDocument
	 */
	public $document;
	public $id;
	/**
	 * @todo Rewrite as method and quess if null.
	 * @var unknown_type
	 */
	public $contentType = '';
	public $xpath;
	public $uuid = 0;
	public $data = array();
	public $dataNodes = array();
	public $events = array();
	public $eventsNodes = array();
	public $eventsGlobal = array();
	/**
	 * @var unknown_type
	 */
	public $frames = array();
	/**
	 * Document root, by default equals to document itself.
	 * Used by documentFragments.
	 *
	 * @var \DOMNode
	 */
	public $root;
	public $isDocumentFragment;
	public $isXML = false;
	public $isXHTML = false;
	public $isHTML = false;
	public $charset;
	public function __construct($markup = null, $contentType = null, $newDocumentID = null) {
		if (isset($markup))
			$this->load($markup, $contentType, $newDocumentID);
		$this->id = $newDocumentID
			? $newDocumentID
			: md5(microtime());
	}
	public function load($markup, $contentType = null, $newDocumentID = null) {
		$this->contentType = strtolower($contentType);
		if ($markup instanceof \DOMDOCUMENT) {
			$this->document = $markup;
			$this->root = $this->document;
			$this->charset = $this->document->encoding;
		} else {
			$loaded = $this->loadMarkup($markup);
		}
		if ($loaded) {
			$this->document->preserveWhiteSpace = true;
			$this->xpath = new \DOMXPath($this->document);
			$this->afterMarkupLoad();
			return true;
		}
		return false;
	}
	protected function afterMarkupLoad() {
		if ($this->isXHTML) {
			$this->xpath->registerNamespace("html", "http://www.w3.org/1999/xhtml");
		}
	}
	protected function loadMarkup($markup) {
		$loaded = false;
		if ($this->contentType) {
			self::debug("Load markup for content type {$this->contentType}");
			list($contentType, $charset) = $this->contentTypeToArray($this->contentType);
			switch($contentType) {
				case 'text/html':
					ArcaelaDOM::debug("Loading HTML, content type '{$this->contentType}'");
					$loaded = $this->loadMarkupHTML($markup, $charset);
				break;
				case 'text/xml':
				case 'application/xhtml+xml':
					ArcaelaDOM::debug("Loading XML, content type '{$this->contentType}'");
					$loaded = $this->loadMarkupXML($markup, $charset);
				break;
				default:
					if (strpos('xml', (string) $this->contentType) !== false) {
						ArcaelaDOM::debug("Loading XML, content type '{$this->contentType}'");
						$loaded = $this->loadMarkupXML($markup, $charset);
					} else
						ArcaelaDOM::debug("Could not determine document type from content type '{$this->contentType}'");
			}
		} else {
			if ($this->isXML($markup)) {
				ArcaelaDOM::debug("Loading XML, isXML() == true");
				$loaded = $this->loadMarkupXML($markup);
				if (! $loaded && $this->isXHTML) {
					ArcaelaDOM::debug('Loading as XML failed, trying to load as HTML, isXHTML == true');
					$loaded = $this->loadMarkupHTML($markup);
				}
			} else {
				ArcaelaDOM::debug("Loading HTML, isXML() == false");
				$loaded = $this->loadMarkupHTML($markup);
			}
		}
		return $loaded;
	}
	protected function loadMarkupReset() {
		$this->isXML = $this->isXHTML = $this->isHTML = false;
	}
	protected function documentCreate($charset, $version = '1.0') {
		if (! $version)
			$version = '1.0';
		$this->document = new \DOMDocument($version, $charset);
		$this->charset = $this->document->encoding;
		$this->document->formatOutput = true;
		$this->document->preserveWhiteSpace = true;
	}
	protected function loadMarkupHTML($markup, $requestedCharset = null) {
		if (ArcaelaDOM::$debug)
			ArcaelaDOM::debug('Full markup load (HTML): '.substr($markup, 0, 250));
		$this->loadMarkupReset();
		$this->isHTML = true;
		if (!isset($this->isDocumentFragment))
			$this->isDocumentFragment = self::isDocumentFragmentHTML($markup);
		$charset = null;
		$documentCharset = $this->charsetFromHTML($markup);
		$addDocumentCharset = false;
		if ($documentCharset) {
			$charset = $documentCharset;
			$markup = $this->charsetFixHTML($markup);
		} else if ($requestedCharset) {
			$charset = $requestedCharset;
		}
		if (! $charset)
			$charset = ArcaelaDOM::$defaultCharset;
		if (! $documentCharset) {
			$documentCharset = 'ISO-8859-1';
			$addDocumentCharset = true;	
		}
		$requestedCharset = strtoupper($requestedCharset);
		$documentCharset = strtoupper($documentCharset);
		ArcaelaDOM::debug("DOC: $documentCharset REQ: $requestedCharset");
		if ($requestedCharset && $documentCharset && $requestedCharset !== $documentCharset) {
			ArcaelaDOM::debug("CHARSET CONVERT");
			if (function_exists('mb_detect_encoding')) {
				$possibleCharsets = array($documentCharset, $requestedCharset, 'AUTO');
				$docEncoding = mb_detect_encoding($markup, implode(', ', $possibleCharsets));
				if (! $docEncoding)
					$docEncoding = $documentCharset;
				ArcaelaDOM::debug("DETECTED '$docEncoding'");
				if ($docEncoding !== $documentCharset) {
				}
				if ($docEncoding !== $requestedCharset) {
					ArcaelaDOM::debug("CONVERT $docEncoding => $requestedCharset");
					$markup = mb_convert_encoding($markup, $requestedCharset, $docEncoding);
					$markup = $this->charsetAppendToHTML($markup, $requestedCharset);
					$charset = $requestedCharset;
				}
			} else {
				ArcaelaDOM::debug("TODO: charset conversion without mbstring...");
			}
		}
		$return = false;
		if ($this->isDocumentFragment) {
			ArcaelaDOM::debug("Full markup load (HTML), DocumentFragment detected, using charset '$charset'");
			$return = $this->documentFragmentLoadMarkup($this, $charset, $markup);
		} else {
			if ($addDocumentCharset) {
				ArcaelaDOM::debug("Full markup load (HTML), appending charset: '$charset'");
				$markup = $this->charsetAppendToHTML($markup, $charset);
			}
			ArcaelaDOM::debug("Full markup load (HTML), documentCreate('$charset')");
			$this->documentCreate($charset);
			$return = ArcaelaDOM::$debug === 2
				? $this->document->loadHTML($markup)
				: @$this->document->loadHTML($markup);
			if ($return)
				$this->root = $this->document;
		}
		if ($return && ! $this->contentType)
			$this->contentType = 'text/html';
		return $return;
	}
	protected function loadMarkupXML($markup, $requestedCharset = null) {
		if (ArcaelaDOM::$debug)
			ArcaelaDOM::debug('Full markup load (XML): '.substr($markup, 0, 250));
		$this->loadMarkupReset();
		$this->isXML = true;
		$isContentTypeXHTML = $this->isXHTML();
		$isMarkupXHTML = $this->isXHTML($markup);
		if ($isContentTypeXHTML || $isMarkupXHTML) {
			self::debug('Full markup load (XML), XHTML detected');
			$this->isXHTML = true;
		}
		if (! isset($this->isDocumentFragment))
			$this->isDocumentFragment = $this->isXHTML
				? self::isDocumentFragmentXHTML($markup)
				: self::isDocumentFragmentXML($markup);
		$charset = null;
		$documentCharset = $this->charsetFromXML($markup);
		if (! $documentCharset) {
			if ($this->isXHTML) {
				$documentCharset = $this->charsetFromHTML($markup);
				if ($documentCharset) {
					ArcaelaDOM::debug("Full markup load (XML), appending XHTML charset '$documentCharset'");
					$this->charsetAppendToXML($markup, $documentCharset);
					$charset = $documentCharset;
				}
			}
			if (! $documentCharset) {
				$charset = $requestedCharset;
			}
		} else if ($requestedCharset) {
			$charset = $requestedCharset;
		}
		if (! $charset) {
			$charset = ArcaelaDOM::$defaultCharset;
		}
		if ($requestedCharset && $documentCharset && $requestedCharset != $documentCharset) {
		}
		$return = false;
		if ($this->isDocumentFragment) {
			ArcaelaDOM::debug("Full markup load (XML), DocumentFragment detected, using charset '$charset'");
			$return = $this->documentFragmentLoadMarkup($this, $charset, $markup);
		} else {
			if ($isContentTypeXHTML && ! $isMarkupXHTML)
			if (! $documentCharset) {
				ArcaelaDOM::debug("Full markup load (XML), appending charset '$charset'");
				$markup = $this->charsetAppendToXML($markup, $charset);
			}
			$this->documentCreate($charset);
			if (phpversion() < 5.1) {
				$this->document->resolveExternals = true;
				$return = ArcaelaDOM::$debug === 2
					? $this->document->loadXML($markup)
					: @$this->document->loadXML($markup);
			} else {
				/** @link http://pl2.php.net/manual/en/libxml.constants.php */
				$libxmlStatic = ArcaelaDOM::$debug === 2
					? LIBXML_DTDLOAD|LIBXML_DTDATTR|LIBXML_NONET
					: LIBXML_DTDLOAD|LIBXML_DTDATTR|LIBXML_NONET|LIBXML_NOWARNING|LIBXML_NOERROR;
				$return = $this->document->loadXML($markup, $libxmlStatic);
			}
			if ($return)
				$this->root = $this->document;
		}
		if ($return) {
			if (! $this->contentType) {
				if ($this->isXHTML)
					$this->contentType = 'application/xhtml+xml';
				else
					$this->contentType = 'text/xml';
			}
			return $return;
		} else {
			throw new \Exception("Error loading XML markup");
		}
	}
	protected function isXHTML($markup = null) {
		if (! isset($markup)) {
			return strpos((string) $this->contentType, 'xhtml') !== false;
		}
		return strpos($markup, "<!DOCTYPE html") !== false;
	}
	protected function isXML($markup) {
		return strpos(substr($markup, 0, 100), '<'.'?xml') !== false;
	}
	protected function contentTypeToArray($contentType) {
		$matches = explode(';', trim(strtolower($contentType)));
		if (isset($matches[1])) {
			$matches[1] = explode('=', $matches[1]);
			$matches[1] = isset($matches[1][1]) && trim($matches[1][1])
				? $matches[1][1]
				: $matches[1][0];
		} else
			$matches[1] = null;
		return $matches;
	}
	/**
	 *
	 * @param $markup
	 * @return array contentType, charset
	 */
	protected function contentTypeFromHTML($markup) {
		$matches = array();
		preg_match('@<meta[^>]+http-equiv\\s*=\\s*(["|\'])Content-Type\\1([^>]+?)>@i',
			$markup, $matches
		);
		if (! isset($matches[0]))
			return array(null, null);
		preg_match('@content\\s*=\\s*(["|\'])(.+?)\\1@', $matches[0], $matches);
		if (! isset($matches[0]))
			return array(null, null);
		return $this->contentTypeToArray($matches[2]);
	}
	protected function charsetFromHTML($markup) {
		$contentType = $this->contentTypeFromHTML($markup);
		return $contentType[1];
	}
	protected function charsetFromXML($markup) {
		$matches = [];
		preg_match('@<'.'?xml[^>]+encoding\\s*=\\s*(["|\'])(.*?)\\1@i',
			$markup, $matches
		);
		return isset($matches[2])
			? strtolower($matches[2])
			: null;
	}
	/**
	 * Repositions meta[type=charset] at the start of head. Bypasses \DOMDocument bug.
	 *
	 * @param $html
	 */
	protected function charsetFixHTML($markup) {
		$matches = array();
		preg_match('@\s*<meta[^>]+http-equiv\\s*=\\s*(["|\'])Content-Type\\1([^>]+?)>@i',
			$markup, $matches, PREG_OFFSET_CAPTURE
		);
		if (! isset($matches[0]))
			return;
		$metaContentType = $matches[0][0];
		$markup = substr($markup, 0, $matches[0][1])
			.substr($markup, $matches[0][1]+strlen($metaContentType));
		$headStart = stripos($markup, '<head>');
		$markup = substr($markup, 0, $headStart+6).$metaContentType
			.substr($markup, $headStart+6);
		return $markup;
	}
	protected function charsetAppendToHTML($html, $charset, $xhtml = false) {
		$html = preg_replace('@\s*<meta[^>]+http-equiv\\s*=\\s*(["|\'])Content-Type\\1([^>]+?)>@i', '', $html);
		$meta = '<meta http-equiv="Content-Type" content="text/html;charset='
			.$charset.'" '
			.($xhtml ? '/' : '')
			.'>';
		if (strpos($html, '<head') === false) {
			if (strpos($html, '<html') === false) {
				return $meta.$html;
			} else {
				return preg_replace(
					'@<html(.*?)(?(?<!\?)>)@s',
					"<html\\1><head>{$meta}</head>",
					$html
				);
			}
		} else {
			return preg_replace(
				'@<head(.*?)(?(?<!\?)>)@s',
				'<head\\1>'.$meta,
				$html
			);
		}
	}
	protected function charsetAppendToXML($markup, $charset) {
		$declaration = '<'.'?xml version="1.0" encoding="'.$charset.'"?'.'>';
		return $declaration.$markup;
	}
	public static function isDocumentFragmentHTML($markup) {
		return stripos($markup, '<html') === false && stripos($markup, '<!doctype') === false;
	}
	public static function isDocumentFragmentXML($markup) {
		return stripos($markup, '<'.'?xml') === false;
	}
	public static function isDocumentFragmentXHTML($markup) {
		return self::isDocumentFragmentHTML($markup);
	}
	public function importAttr($value) {
	}
	/**
	 *
	 * @param $source
	 * @param $target
	 * @param $sourceCharset
	 * @return array Array of imported nodes.
	 */
	public function import($source, $sourceCharset = null) {
		$return = array();
		if ($source instanceof \DOMNODE && !($source instanceof \DOMNODELIST))
			$source = array($source);
		if (is_array($source) || $source instanceof \DOMNODELIST) {
			self::debug('Importing nodes to document');
			foreach($source as $node)
				$return[] = $this->document->importNode($node, true);
		} else {
			$fake = $this->documentFragmentCreate($source, $sourceCharset);
			if ($fake === false)
				throw new \Exception("Error loading documentFragment markup");
			else
				return $this->import($fake->root->childNodes);
		}
		return $return;
	}
	/**
	 * Creates new document fragment.
	 *
	 * @param $source
	 * @return \DOMDocumentWrapper
	 */
	protected function documentFragmentCreate($source, $charset = null) {
		$fake = new DOMDocumentWrapper();
		$fake->contentType = $this->contentType;
		$fake->isXML = $this->isXML;
		$fake->isHTML = $this->isHTML;
		$fake->isXHTML = $this->isXHTML;
		$fake->root = $fake->document;
		if (! $charset)
			$charset = $this->charset;
		if ($source instanceof \DOMNODE && !($source instanceof \DOMNODELIST))
			$source = array($source);
		if (is_array($source) || $source instanceof \DOMNODELIST) {
			if (! $this->documentFragmentLoadMarkup($fake, $charset))
				return false;
			$nodes = $fake->import($source);
			foreach($nodes as $node)
				$fake->root->appendChild($node);
		} else {
			$this->documentFragmentLoadMarkup($fake, $charset, $source);
		}
		return $fake;
	}
	/**
	 *
	 * @param $document \DOMDocumentWrapper
	 * @param $markup
	 * @return $document
	 */
	private function documentFragmentLoadMarkup($fragment, $charset, $markup = null) {
		$fragment->isDocumentFragment = false;
		if ($fragment->isXML) {
			if ($fragment->isXHTML) {
				$fragment->loadMarkupXML('<?xml version="1.0" encoding="'.$charset.'"?>'
					.'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" '
					.'"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'
					.'<fake xmlns="http://www.w3.org/1999/xhtml">'.$markup.'</fake>');
				$fragment->root = $fragment->document->firstChild->nextSibling;
			} else {
				$fragment->loadMarkupXML('<?xml version="1.0" encoding="'.$charset.'"?><fake>'.$markup.'</fake>');
				$fragment->root = $fragment->document->firstChild;
			}
		} else {
			$markup2 = ArcaelaDOM::$defaultDoctype.'<html><head><meta http-equiv="Content-Type" content="text/html;charset='
				.$charset.'"></head>';
			$noBody = strpos($markup, '<body') === false;
			if ($noBody)
				$markup2 .= '<body>';
			$markup2 .= $markup;
			if ($noBody)
				$markup2 .= '</body>';
			$markup2 .= '</html>';
			$fragment->loadMarkupHTML($markup2);
			$fragment->root = $noBody
				? $fragment->document->firstChild->nextSibling->firstChild->nextSibling
				: $fragment->document->firstChild->nextSibling->firstChild->nextSibling;
		}
		if (! $fragment->root)
			return false;
		$fragment->isDocumentFragment = true;
		return true;
	}
	protected function documentFragmentToMarkup($fragment) {
		ArcaelaDOM::debug('documentFragmentToMarkup');
		$tmp = $fragment->isDocumentFragment;
		$fragment->isDocumentFragment = false;
		$markup = $fragment->markup();
		if ($fragment->isXML) {
			$markup = substr($markup, 0, strrpos($markup, '</fake>'));
			if ($fragment->isXHTML) {
				$markup = substr($markup, strpos($markup, '<fake')+43);
			} else {
				$markup = substr($markup, strpos($markup, '<fake>')+6);
			}
		} else {
				$markup = substr($markup, strpos($markup, '<body>')+6);
				$markup = substr($markup, 0, strrpos($markup, '</body>'));
		}
		$fragment->isDocumentFragment = $tmp;
		if (ArcaelaDOM::$debug)
			ArcaelaDOM::debug('documentFragmentToMarkup: '.substr($markup, 0, 150));
		return $markup;
	}
	/**
	 * Return document markup, starting with optional $nodes as root.
	 *
	 * @param $nodes	DOMNode|DOMNodeList
	 * @return string
	 */
	public function markup($nodes = null, $innerMarkup = false) {
		if (isset($nodes) && count($nodes) == 1 && $nodes[0] instanceof \DOMDOCUMENT)
			$nodes = null;
		if (isset($nodes)) {
			$markup = '';
			if (!is_array($nodes) && !($nodes instanceof \DOMNODELIST) )
				$nodes = array($nodes);
			if ($this->isDocumentFragment && ! $innerMarkup)
				foreach($nodes as $i => $node)
					if ($node->isSameNode($this->root)) {
						$nodes = array_slice($nodes, 0, $i)
							+ ArcaelaDOM::DOMNodeListToArray($node->childNodes)
							+ array_slice($nodes, $i+1);
						}
			if ($this->isXML && ! $innerMarkup) {
				self::debug("Getting outerXML with charset '{$this->charset}'");
				foreach($nodes as $node)
					$markup .= $this->document->saveXML($node);
			} else {
				$loop = array();
				if ($innerMarkup)
					foreach($nodes as $node) {
						if ($node->childNodes)
							foreach($node->childNodes as $child)
								$loop[] = $child;
						else
							$loop[] = $node;
					}
				else
					$loop = $nodes;
				self::debug("Getting markup, moving selected nodes (".count($loop).") to new DocumentFragment");
				$fake = $this->documentFragmentCreate($loop);
				$markup = $this->documentFragmentToMarkup($fake);
			}
			if ($this->isXHTML) {
				self::debug("Fixing XHTML");
				$markup = self::markupFixXHTML($markup);
			}
			self::debug("Markup: ".substr($markup, 0, 250));
			return $markup;
		} else {
			if ($this->isDocumentFragment) {
				self::debug("Getting markup, DocumentFragment detected");
				$markup = $this->documentFragmentToMarkup($this);
				return $markup;
			} else {
				self::debug("Getting markup (".($this->isXML?'XML':'HTML')."), final with charset '{$this->charset}'");
				$markup = $this->isXML
					? $this->document->saveXML()
					: $this->document->saveHTML();
				if ($this->isXHTML) {
					self::debug("Fixing XHTML");
					$markup = self::markupFixXHTML($markup);
				}
				self::debug("Markup: ".substr($markup, 0, 250));
				return $markup;
			}
		}
	}
	protected static function markupFixXHTML($markup) {
		$markup = self::expandEmptyTag('script', $markup);
		$markup = self::expandEmptyTag('select', $markup);
		$markup = self::expandEmptyTag('textarea', $markup);
		return $markup;
	}
	public static function debug($text) {
		ArcaelaDOM::debug($text);
	}
	/**
	 * expandEmptyTag
	 *
	 * @param $tag
	 * @param $xml
	 * @return unknown_type
	 * @link http://php.net/manual/en/domdocument.savehtml.php#81256
	 */
	public static function expandEmptyTag($tag, $xml){
        $indice = 0;
        while ($indice< strlen($xml)){
            $pos = strpos($xml, "<$tag ", $indice);
            if ($pos){
                $posCierre = strpos($xml, ">", $pos);
                if ($xml[$posCierre-1] == "/"){
                    $xml = substr_replace($xml, "></$tag>", $posCierre-1, 2);
                }
                $indice = $posCierre;
            }
            else break;
        }
        return $xml;
	}
}
/**
 * Event handling class.
 *
 * @package ArcaelaDOM
 * @static
 */
abstract class ArcaelaDOMEvents {
	/**
	 * Trigger a type of event on every matched element.
	 *
	 * @param \DOMNode|ArcaelaElement|string $document
	 * @param unknown_type $type
	 * @param unknown_type $data
	 *
	 * @TODO exclusive events (with !)
	 * @TODO global events (test)
	 * @TODO support more than event in $type (space-separated)
	 */
	public static function trigger($document, $type, $data = array(), $node = null) {
		$documentID = ArcaelaDOM::getDocumentID($document);
		$namespace = null;
		if (strpos((string) $type, '.') !== false)
			list($name, $namespace) = explode('.', (string) $type);
		else
			$name = $type;
		if (! $node) {
			if (self::issetGlobal($documentID, $type)) {
				$pq = ArcaelaDOM::getDocument($documentID);
				$pq->find('*')->add($pq->document)
					->trigger($type, $data);
			}
		} else {
			if (isset($data[0]) && $data[0] instanceof DOMEvent) {
				$event = $data[0];
				$event->relatedTarget = $event->target;
				$event->target = $node;
				$data = array_slice($data, 1);
			} else {
				$event = new DOMEvent(array(
					'type' => $type,
					'target' => $node,
					'timeStamp' => time(),
				));
			}
			$i = 0;
			while($node) {
				ArcaelaDOM::debug("Triggering ".($i?"bubbled ":'')."event '{$type}' on "
					."node \n");//.ArcaelaElement::whois($node)."\n");
				$event->currentTarget = $node;
				$eventNode = self::getNode($documentID, $node);
				if (isset($eventNode->eventHandlers)) {
					foreach($eventNode->eventHandlers as $eventType => $handlers) {
						$eventNamespace = null;
						if (strpos((string) $type, '.') !== false)
							list($eventName, $eventNamespace) = explode('.', $eventType);
						else
							$eventName = $eventType;
						if ($name != $eventName)
							continue;
						if ($namespace && $eventNamespace && $namespace != $eventNamespace)
							continue;
						foreach($handlers as $handler) {
							ArcaelaDOM::debug("Calling event handler\n");
							$event->data = $handler['data']
								? $handler['data']
								: null;
							$params = array_merge(array($event), $data);
							$return = ArcaelaDOM::callbackRun($handler['callback'], $params);
							if ($return === false) {
								$event->bubbles = false;
							}
						}
					}
				}
				if (! $event->bubbles)
					break;
				$node = $node->parentNode;
				$i++;
			}
		}
	}
	/**
	 * Binds a handler to one or more events (like click) for each matched element.
	 * Can also bind custom events.
	 *
	 * @param \DOMNode|ArcaelaElement|string $document
	 * @param unknown_type $type
	 * @param unknown_type $data Optional
	 * @param unknown_type $callback
	 *
	 * @TODO support '!' (exclusive) events
	 * @TODO support more than event in $type (space-separated)
	 * @TODO support binding to global events
	 */
	public static function add($document, $node, $type, $data, $callback = null) {
		ArcaelaDOM::debug("Binding '$type' event");
		$documentID = ArcaelaDOM::getDocumentID($document);
		$eventNode = self::getNode($documentID, $node);
		if (! $eventNode)
			$eventNode = self::setNode($documentID, $node);
		if (!isset($eventNode->eventHandlers[$type]))
			$eventNode->eventHandlers[$type] = array();
		$eventNode->eventHandlers[$type][] = array(
			'callback' => $callback,
			'data' => $data,
		);
	}
	/**
	 * Enter description here...
	 *
	 * @param \DOMNode|ArcaelaElement|string $document
	 * @param unknown_type $type
	 * @param unknown_type $callback
	 *
	 * @TODO namespace events
	 * @TODO support more than event in $type (space-separated)
	 */
	public static function remove($document, $node, $type = null, $callback = null) {
		$documentID = ArcaelaDOM::getDocumentID($document);
		$eventNode = self::getNode($documentID, $node);
		if (is_object($eventNode) && isset($eventNode->eventHandlers[$type])) {
			if ($callback) {
				foreach($eventNode->eventHandlers[$type] as $k => $handler)
					if ($handler['callback'] == $callback)
						unset($eventNode->eventHandlers[$type][$k]);
			} else {
				unset($eventNode->eventHandlers[$type]);
			}
		}
	}
	protected static function getNode($documentID, $node) {
		foreach(ArcaelaDOM::$documents[$documentID]->eventsNodes as $eventNode) {
			if ($node->isSameNode($eventNode))
				return $eventNode;
		}
	}
	protected static function setNode($documentID, $node) {
		ArcaelaDOM::$documents[$documentID]->eventsNodes[] = $node;
		return ArcaelaDOM::$documents[$documentID]->eventsNodes[
			count(ArcaelaDOM::$documents[$documentID]->eventsNodes)-1
		];
	}
	protected static function issetGlobal($documentID, $type) {
		return isset(ArcaelaDOM::$documents[$documentID])
			? in_array($type, ArcaelaDOM::$documents[$documentID]->eventsGlobal)
			: false;
	}
}
interface ICallbackNamed {
	function hasName();
	function getName();
}
/**
 * Callback class introduces currying-like pattern.
 * 
 * Example:
 * function foo($param1, $param2, $param3) {
 *   var_dump($param1, $param2, $param3);
 * }
 * $fooCurried = new Callback('foo', 
 *   'param1 is now statically set', 
 *   new CallbackParam, new CallbackParam
 * );
 * ArcaelaDOM::callbackRun($fooCurried,
 * 	array('param2 value', 'param3 value'
 * );
 * 
 * Callback class is supported in all ArcaelaDOM methods which accepts callbacks. 
 *
 * @TODO??? return fake forwarding function created via create_function
 * @TODO honor paramStructure
 */
class Callback
	implements ICallbackNamed {
	public $callback = null;
	public $params = null;
	protected $name;
	public function __construct($callback, $param1 = null, $param2 = null, 
			$param3 = null) {
		$params = func_get_args();
		$params = array_slice($params, 1);
		if ($callback instanceof Callback) {
		} else {
			$this->callback = $callback;
			$this->params = $params;
		}
	}
	public function getName() {
		return 'Callback: '.$this->name;
	}
	public function hasName() {
		return isset($this->name) && $this->name;
	}
	public function setName($name) {
		$this->name = $name;
		return $this;
	}
}
/**
 * Shorthand for new Callback(create_function(...), ...);
 * 
 */
class CallbackBody extends Callback {
	public function __construct($paramList, $code, $param1 = null, $param2 = null, 
			$param3 = null) {
		$params = func_get_args();
		$params = array_slice($params, 2);
		$this->callback = create_function($paramList, $code);
		$this->params = $params;
	}
}
/**
 * Callback type which on execution returns reference passed during creation.
 * 
 */
class CallbackReturnReference extends Callback
	implements ICallbackNamed {
	protected $reference;
	public function __construct(&$reference, $name = null){
		$this->reference =& $reference;
		$this->callback = array($this, 'callback');
	}
	public function callback() {
		return $this->reference;
	}
	public function getName() {
		return 'Callback: '.$this->name;
	}
	public function hasName() {
		return isset($this->name) && $this->name;
	}
}
/**
 * Callback type which on execution returns value passed during creation.
 * 
 */
class CallbackReturnValue extends Callback
	implements ICallbackNamed {
	protected $value;
	protected $name;
	public function __construct($value, $name = null){
		$this->value =& $value;
		$this->name = $name;
		$this->callback = array($this, 'callback');
	}
	public function callback() {
		return $this->value;
	}
	public function __toString() {
		return $this->getName();
	}
	public function getName() {
		return 'Callback: '.$this->name;
	}
	public function hasName() {
		return isset($this->name) && $this->name;
	}
}
/**
 * CallbackParameterToReference can be used when we don't really want a callback,
 * only parameter passed to it. CallbackParameterToReference takes first 
 * parameter's value and passes it to reference.
 *
 */
class CallbackParameterToReference extends Callback {
	/**
	 * @param $reference
	 * @TODO implement $paramIndex; 
	 * param index choose which callback param will be passed to reference
	 */
	public function __construct(&$reference){
		$this->callback =& $reference;
	}
}
class CallbackParam {}
/**
 * Class representing ArcaelaDOM objects.
 *
 * @package ArcaelaDOM
 * @method ArcaelaElement clone() clone()
 * @method ArcaelaElement empty() empty()
 * @method ArcaelaElement next() next($selector = null)
 * @method ArcaelaElement prev() prev($selector = null)
 * @property Int $length
 */
class ArcaelaElement implements \Iterator, \Countable, \ArrayAccess {
	public $documentID = null;
	/**
	 * \DOMDocument class.
	 *
	 * @var \DOMDocument
	 */
	public $document = null;
	public $charset = null;
	/**
	 *
	 * @var \DOMDocumentWrapper
	 */
	public $documentWrapper = null;
	/**
	 * XPath interface.
	 *
	 * @var \DOMXPath
	 */
	public $xpath = null;
	/**
	 * Stack of selected elements.
	 * @TODO refactor to ->nodes
	 * @var array
	 */
	public $elements = array();
	/**
	 * @access private
	 */
	protected $elementsBackup = array();
	/**
	 * @access private
	 */
	protected $previous = null;
	/**
	 * @access private
	 * @TODO deprecate
	 */
	protected $root = array();
	/**
	 * Indicated if doument is just a fragment (no <html> tag).
	 *
	 * Every document is realy a full document, so even documentFragments can
	 * be queried against <html>, but getDocument(id)->htmlOuter() will return
	 * only contents of <body>.
	 *
	 * @var bool
	 */
	public $documentFragment = true;
	/**
	 * Iterator interface helper
	 * @access private
	 */
	protected $elementsInterator = array();
	/**
	 * Iterator interface helper
	 * @access private
	 */
	protected $valid = false;
	/**
	 * Iterator interface helper
	 * @access private
	 */
	protected $current = null;
	/**
	 * Enter description here...
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function __construct($documentID) {
		$id = $documentID instanceof self
			? $documentID->getDocumentID()
			: $documentID;
		if (! isset(ArcaelaDOM::$documents[$id] )) {
			throw new \Exception("Document with ID '{$id}' isn't loaded. Use ArcaelaDOM::newDocument(\$html) or ArcaelaDOM::newDocumentFile(\$file) first.");
		}
		$this->documentID = $id;
		$this->documentWrapper =& ArcaelaDOM::$documents[$id];
		$this->document =& $this->documentWrapper->document;
		$this->xpath =& $this->documentWrapper->xpath;
		$this->charset =& $this->documentWrapper->charset;
		$this->documentFragment =& $this->documentWrapper->isDocumentFragment;
		$this->root =& $this->documentWrapper->root;
		$this->elements = array($this->root);
	}
	/**
	 *
	 * @access private
	 * @param $attr
	 * @return unknown_type
	 */
	public function __get($attr) {
		switch($attr) {
			case 'length':
				return $this->size();
			break;
			default:
				return $this->$attr;
		}
	}
	/**
	 * Saves actual object to $var by reference.
	 * Useful when need to break chain.
	 * @param ArcaelaElement $var
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function toReference(&$var) {
		return $var = $this;
	}
	public function documentFragment($state = null) {
		if ($state) {
			ArcaelaDOM::$documents[$this->getDocumentID()]['documentFragment'] = $state;
			return $this;
		}
		return $this->documentFragment;
	}
	/**
   * @access private
   * @TODO documentWrapper
	 */
	protected function isRoot( $node) {
		return $node instanceof \DOMDOCUMENT
			|| ($node instanceof \DOMELEMENT && $node->tagName == 'html')
			|| $this->root->isSameNode($node);
	}
	/**
   * @access private
	 */
	protected function stackIsRoot() {
		return $this->size() == 1 && $this->isRoot($this->elements[0]);
	}
	/**
	 * Enter description here...
	 * NON JQUERY METHOD
	 *
	 * Watch out, it doesn't creates new instance, can be reverted with end().
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function toRoot() {
		$this->elements = array($this->root);
		return $this;
	}
	/**
	 * Saves object's DocumentID to $var by reference.
	 * <code>
	 * $myDocumentId;
	 * ArcaelaDOM::newDocument('<div/>')
	 *     ->getDocumentIDRef($myDocumentId)
	 *     ->find('div')->...
	 * </code>
	 *
	 * @param unknown_type $domId
	 * @see ArcaelaDOM::newDocument
	 * @see ArcaelaDOM::newDocumentFile
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function getDocumentIDRef(&$documentID) {
		$documentID = $this->getDocumentID();
		return $this;
	}
	/**
	 * Returns object with stack set to document root.
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function getDocument() {
		return ArcaelaDOM::getDocument($this->getDocumentID());
	}
	/**
	 *
	 * @return \DOMDocument
	 */
	public function getDOMDocument() {
		return $this->document;
	}
	/**
	 * Get object's Document ID.
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function getDocumentID() {
		return $this->documentID;
	}
	/**
	 * Unloads whole document from memory.
	 * CAUTION! None further operations will be possible on this document.
	 * All objects refering to it will be useless.
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function unloadDocument() {
		ArcaelaDOM::unloadDocuments($this->getDocumentID());
	}
	public function isHTML() {
		return $this->documentWrapper->isHTML;
	}
	public function isXHTML() {
		return $this->documentWrapper->isXHTML;
	}
	public function isXML() {
		return $this->documentWrapper->isXML;
	}
	/**
	 * Enter description here...
	 *
	 * @link http://docs.jquery.com/Ajax/serialize
	 * @return string
	 */
	public function serialize() {
		return ArcaelaDOM::param($this->serializeArray());
	}
	/**
	 * Enter description here...
	 *
	 * @link http://docs.jquery.com/Ajax/serializeArray
	 * @return array
	 */
	public function serializeArray($submit = null) {
		$source = $this->filter('form, input, select, textarea')
			->find('input, select, textarea')
			->andSelf()
			->not('form');
		$return = array();
		foreach($source as $input) {
			$input = ArcaelaDOM::querySelector($input);
			if ($input->is('[disabled]'))
				continue;
			if (!$input->is('[name]'))
				continue;
			if ($input->is('[type=checkbox]') && !$input->is('[checked]'))
				continue;
			if ($submit && $input->is('[type=submit]')) {
				if ($submit instanceof \DOMELEMENT && ! $input->elements[0]->isSameNode($submit))
					continue;
				else if (is_string($submit) && $input->attr('name') != $submit)
					continue;
			}
			$return[] = array(
				'name' => $input->attr('name'),
				'value' => $input->val(),
			);
		}
		return $return;
	}
	/**
	 * @access private
	 */
	protected function debug($in) {
		if (! ArcaelaDOM::$debug )
			return;
		print('<pre>');
		print_r($in);
		print("</pre>\n");
	}
	/**
	 * @access private
	 */
	protected function isRegexp($pattern) {
		return in_array(
			$pattern[ mb_strlen($pattern)-1 ],
			array('^','*','$')
		);
	}
	/**
	 * Determines if $char is really a char.
	 *
	 * @param string $char
	 * @return bool
	 * @todo rewrite me to charcode range ! ;)
	 * @access private
	 */
	protected function isChar($char) {
		return extension_loaded('mbstring') && ArcaelaDOM::$mbstringSupport
			? mb_eregi('\w', $char)
			: preg_match('@\w@', $char);
	}
	/**
	 * @access private
	 */
	protected function parseSelector($query) {
		$query = trim(
			preg_replace('@\s+@', ' ',
				preg_replace('@\s*(>|\\+|~)\s*@', '\\1', $query)
			)
		);
		$queries = array(array());
		if (! $query)
			return $queries;
		$return =& $queries[0];
		$specialChars = array('>',' ');
		$specialCharsMapping = array();
		$strlen = mb_strlen($query);
		$classChars = array('.', '-');
		$pseudoChars = array('-');
		$tagChars = array('*', '|', '-');
		$_query = array();
		for ($i=0; $i<$strlen; $i++)
			$_query[] = mb_substr($query, $i, 1);
		$query = $_query;
		$i = 0;
		while( $i < $strlen) {
			$c = $query[$i];
			$tmp = '';
			if ($this->isChar($c) || in_array($c, $tagChars)) {
				while(isset($query[$i])
					&& ($this->isChar($query[$i]) || in_array($query[$i], $tagChars))) {
					$tmp .= $query[$i];
					$i++;
				}
				$return[] = $tmp;
			} else if ( $c == '#') {
				$i++;
				while( isset($query[$i]) && ($this->isChar($query[$i]) || $query[$i] == '-')) {
					$tmp .= $query[$i];
					$i++;
				}
				$return[] = '#'.$tmp;
			} else if (in_array($c, $specialChars)) {
				$return[] = $c;
				$i++;
			} else if ( isset($specialCharsMapping[$c])) {
				$return[] = $specialCharsMapping[$c];
				$i++;
			} else if ( $c == ',') {
				$queries[] = array();
				$return =& $queries[ count($queries)-1 ];
				$i++;
				while( isset($query[$i]) && $query[$i] == ' ')
					$i++;
			} else if ($c == '.') {
				while( isset($query[$i]) && ($this->isChar($query[$i]) || in_array($query[$i], $classChars))) {
					$tmp .= $query[$i];
					$i++;
				}
				$return[] = $tmp;
			} else if ($c == '~') {
				$spaceAllowed = true;
				$tmp .= $query[$i++];
				while( isset($query[$i])
					&& ($this->isChar($query[$i])
						|| in_array($query[$i], $classChars)
						|| $query[$i] == '*'
						|| ($query[$i] == ' ' && $spaceAllowed)
					)) {
					if ($query[$i] != ' ')
						$spaceAllowed = false;
					$tmp .= $query[$i];
					$i++;
				}
				$return[] = $tmp;
			} else if ($c == '+') {
				$spaceAllowed = true;
				$tmp .= $query[$i++];
				while( isset($query[$i])
					&& ($this->isChar($query[$i])
						|| in_array($query[$i], $classChars)
						|| $query[$i] == '*'
						|| ($spaceAllowed && $query[$i] == ' ')
					)) {
					if ($query[$i] != ' ')
						$spaceAllowed = false;
					$tmp .= $query[$i];
					$i++;
				}
				$return[] = $tmp;
			} else if ($c == '[') {
				$stack = 1;
				$tmp .= $c;
				while( isset($query[++$i])) {
					$tmp .= $query[$i];
					if ( $query[$i] == '[') {
						$stack++;
					} else if ( $query[$i] == ']') {
						$stack--;
						if (! $stack )
							break;
					}
				}
				$return[] = $tmp;
				$i++;
			} else if ($c == ':') {
				$stack = 1;
				$tmp .= $query[$i++];
				while( isset($query[$i]) && ($this->isChar($query[$i]) || in_array($query[$i], $pseudoChars))) {
					$tmp .= $query[$i];
					$i++;
				}
				if ( isset($query[$i]) && $query[$i] == '(') {
					$tmp .= $query[$i];
					$stack = 1;
					while( isset($query[++$i])) {
						$tmp .= $query[$i];
						if ( $query[$i] == '(') {
							$stack++;
						} else if ( $query[$i] == ')') {
							$stack--;
							if (! $stack )
								break;
						}
					}
					$return[] = $tmp;
					$i++;
				} else {
					$return[] = $tmp;
				}
			} else {
				$i++;
			}
		}
		foreach($queries as $k => $q) {
			if (isset($q[0])) {
				if (isset($q[0][0]) && $q[0][0] == ':')
					array_unshift($queries[$k], '*');
				if ($q[0] != '>')
					array_unshift($queries[$k], ' ');
			}
		}
		return $queries;
	}
	/**
	 * Return matched DOM nodes.
	 *
	 * @param int $index
	 * @return array|DOMElement Single \DOMElement or array of \DOMElement.
	 */
	public function get($index = null, $callback1 = null, $callback2 = null, $callback3 = null) {
		$return = isset($index)
			? (isset($this->elements[$index]) ? $this->elements[$index] : null)
			: $this->elements;
		$args = func_get_args();
		$args = array_slice($args, 1);
		foreach($args as $callback) {
			if (is_array($return))
				foreach($return as $k => $v)
					$return[$k] = ArcaelaDOM::callbackRun($callback, array($v));
			else
				$return = ArcaelaDOM::callbackRun($callback, array($return));
		}
		return $return;
	}
	/**
	 * Return matched DOM nodes.
	 * jQuery difference.
	 *
	 * @param int $index
	 * @return array|string Returns string if $index != null
	 * @todo implement callbacks
	 * @todo return only arrays ?
	 * @todo maybe other name...
	 */
	public function getString($index = null, $callback1 = null, $callback2 = null, $callback3 = null) {
		if ($index)
			$return = $this->eq($index)->text();
		else {
			$return = array();
			for($i = 0; $i < $this->size(); $i++) {
				$return[] = $this->eq($i)->text();
			}
		}
		$args = func_get_args();
		$args = array_slice($args, 1);
		foreach($args as $callback) {
			$return = ArcaelaDOM::callbackRun($callback, array($return));
		}
		return $return;
	}
	/**
	 * Return matched DOM nodes.
	 * jQuery difference.
	 *
	 * @param int $index
	 * @return array|string Returns string if $index != null
	 * @todo implement callbacks
	 * @todo return only arrays ?
	 * @todo maybe other name...
	 */
	public function getStrings($index = null, $callback1 = null, $callback2 = null, $callback3 = null) {
		if ($index)
			$return = $this->eq($index)->text();
		else {
			$return = array();
			for($i = 0; $i < $this->size(); $i++) {
				$return[] = $this->eq($i)->text();
			}
			$args = func_get_args();
			$args = array_slice($args, 1);
		}
		foreach($args as $callback) {
			if (is_array($return))
				foreach($return as $k => $v)
					$return[$k] = ArcaelaDOM::callbackRun($callback, array($v));
			else
				$return = ArcaelaDOM::callbackRun($callback, array($return));
		}
		return $return;
	}
	/**
	 * Returns new instance of actual class.
	 *
	 * @param array $newStack Optional. Will replace old stack with new and move old one to history.c
	 */
	public function newInstance($newStack = null) {
		$class = get_class($this);
		$new = $class != ArcaelaDOM::class
			? new $class($this, $this->getDocumentID())
			: new ArcaelaElement($this->getDocumentID());
		$new->previous = $this;
		if (is_null($newStack)) {
			$new->elements = $this->elements;
			if ($this->elementsBackup)
				$this->elements = $this->elementsBackup;
		} else if (is_string($newStack)) {
			$new->elements = ArcaelaDOM::querySelector($newStack, $this->getDocumentID())->stack();
		} else {
			$new->elements = $newStack;
		}
		return $new;
	}
	/**
	 * Enter description here...
	 *
	 * In the future, when PHP will support XLS 2.0, then we would do that this way:
	 * contains(tokenize(@class, '\s'), "something")
	 * @param unknown_type $class
	 * @param unknown_type $node
	 * @return boolean
	 * @access private
	 */
	protected function matchClasses($class, $node) {
		if ( mb_strpos($class, '.', 1)) {
			$classes = explode('.', substr((string) $class, 1));
			$classesCount = count( $classes );
			$nodeClasses = explode(' ', $node->getAttribute('class') );
			$nodeClassesCount = count( $nodeClasses );
			if ( $classesCount > $nodeClassesCount )
				return false;
			$diff = count(
				array_diff(
					$classes,
					$nodeClasses
				)
			);
			if (! $diff )
				return true;
		} else {
			return in_array(
				substr((string) $class, 1),
				explode(' ', $node->getAttribute('class') )
			);
		}
	}
	/**
	 * @access private
	 */
	protected function runQuery($XQuery, $selector = null, $compare = null) {
		if ($compare && ! method_exists($this, $compare))
			return false;
		$stack = array();
		if (! $this->elements)
			$this->debug('Stack empty, skipping...');
		foreach($this->stack(array(1, 9, 13)) as $k => $stackNode) {
			$detachAfter = false;
			$testNode = $stackNode;
			while ($testNode) {
				if (! $testNode->parentNode && ! $this->isRoot($testNode)) {
					$this->root->appendChild($testNode);
					$detachAfter = $testNode;
					break;
				}
				$testNode = isset($testNode->parentNode)
					? $testNode->parentNode
					: null;
			}
			$xpath = $this->documentWrapper->isXHTML
				? $this->getNodeXpath($stackNode, 'html')
				: $this->getNodeXpath($stackNode);
			$query = $XQuery == '//' && $xpath == '/html[1]'
				? '//*'
				: $xpath.$XQuery;
			$this->debug("XPATH: {$query}");
			$nodes = $this->xpath->query($query);
			$this->debug("QUERY FETCHED");
			if (! $nodes->length )
				$this->debug('Nothing found');
			$debug = array();
			foreach($nodes as $node) {
				$matched = false;
				if ( $compare) {
					ArcaelaDOM::$debug ?
						$this->debug("Found: ".$this->whois( $node ).", comparing with {$compare}()")
						: null;
					$ArcaelaDOMDebug = ArcaelaDOM::$debug;
					ArcaelaDOM::$debug = false;
					if (call_user_func_array(array($this, $compare), array($selector, $node)))
						$matched = true;
					ArcaelaDOM::$debug = $ArcaelaDOMDebug;
				} else {
					$matched = true;
				}
				if ( $matched) {
					if (ArcaelaDOM::$debug)
						$debug[] = $this->whois( $node );
					$stack[] = $node;
				}
			}
			if (ArcaelaDOM::$debug) {
				$this->debug("Matched ".count($debug).": ".implode(', ', $debug));
			}
			if ($detachAfter)
				$this->root->removeChild($detachAfter);
		}
		$this->elements = $stack;
	}
	/**
	 * Enter description here...
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function find($selectors, $context = null, $noHistory = false) {
		if (!$noHistory)
			$this->elementsBackup = $this->elements;
		if ($context) {
			if (! is_array($context) && $context instanceof \DOMELEMENT)
				$this->elements = array($context);
			else if (is_array($context)) {
				$this->elements = array();
				foreach ($context as $c)
					if ($c instanceof \DOMELEMENT)
						$this->elements[] = $c;
			} else if ( $context instanceof self )
				$this->elements = $context->elements;
		}
		$queries = $this->parseSelector($selectors);
		$this->debug(array('FIND', $selectors, $queries));
		$XQuery = '';
		$oldStack = $this->elements;
		$stack = array();
		foreach($queries as $selector) {
			$this->elements = $oldStack;
			$delimiterBefore = false;
			foreach($selector as $s) {
				$isTag = extension_loaded('mbstring') && ArcaelaDOM::$mbstringSupport
					? mb_ereg_match('^[\w|\||-]+$', $s) || $s == '*'
					: preg_match('@^[\w|\||-]+$@', $s) || $s == '*';
				if ($isTag) {
					if ($this->isXML()) {
						if (mb_strpos($s, '|') !== false) {
							$ns = $tag = null;
							list($ns, $tag) = explode('|', $s);
							$XQuery .= "$ns:$tag";
						} else if ($s == '*') {
							$XQuery .= "*";
						} else {
							$XQuery .= "*[local-name()='$s']";
						}
					} else {
						$XQuery .= $s;
					}
				} else if ($s[0] == '#') {
					if ($delimiterBefore)
						$XQuery .= '*';
					$XQuery .= "[@id='".substr($s, 1)."']";
				} else if ($s[0] == '[') {
					if ($delimiterBefore)
						$XQuery .= '*';
					$attr = trim($s, '][');
					$execute = false;
					if (mb_strpos($s, '=')) {
						$value = null;
						list($attr, $value) = explode('=', $attr);
						$value = trim($value, "'\"");
						if ($this->isRegexp($attr)) {
							$attr = substr($attr, 0, -1);
							$execute = true;
							$XQuery .= "[@{$attr}]";
						} else {
							$XQuery .= "[@{$attr}='{$value}']";
						}
					} else {
						$XQuery .= "[@{$attr}]";
					}
					if ($execute) {
						$this->runQuery($XQuery, $s, 'is');
						$XQuery = '';
						if (! $this->length)
							break;
					}
				} else if ($s[0] == '.') {
					if ($delimiterBefore)
						$XQuery .= '*';
					$XQuery .= '[@class]';
					$this->runQuery($XQuery, $s, 'matchClasses');
					$XQuery = '';
					if (! $this->length )
						break;
				} else if ($s[0] == '~') {
					$this->runQuery($XQuery);
					$XQuery = '';
					$this->elements = $this
						->siblings(
							substr($s, 1)
						)->elements;
					if (! $this->length )
						break;
				} else if ($s[0] == '+') {
					$this->runQuery($XQuery);
					$XQuery = '';
					$subSelector = substr($s, 1);
					$subElements = $this->elements;
					$this->elements = array();
					foreach($subElements as $node) {
						$test = $node->nextSibling;
						while($test && ! ($test instanceof \DOMELEMENT))
							$test = $test->nextSibling;
						if ($test && $this->is($subSelector, $test))
							$this->elements[] = $test;
					}
					if (! $this->length )
						break;
				} else if ($s[0] == ':') {
					if ($XQuery) {
						$this->runQuery($XQuery);
						$XQuery = '';
					}
					if (! $this->length)
						break;
					$this->pseudoClasses($s);
					if (! $this->length)
						break;
				} else if ($s == '>') {
					$XQuery .= '/';
					$delimiterBefore = 2;
				} else if ($s == ' ') {
					$XQuery .= '//';
					$delimiterBefore = 2;
				} else {
					ArcaelaDOM::debug("Unrecognized token '$s'");
				}
				$delimiterBefore = $delimiterBefore === 2;
			}
			if ($XQuery && $XQuery != '//') {
				$this->runQuery($XQuery);
				$XQuery = '';
			}
			foreach($this->elements as $node)
				if (! $this->elementsContainsNode($node, $stack))
					$stack[] = $node;
		}
		$this->elements = $stack;
		return $this->newInstance();
	}
	/**
	 * @todo create API for classes with pseudoselectors
	 * @access private
	 */
	protected function pseudoClasses($class) {
		$class = ltrim($class, ':');
		$haveArgs = mb_strpos($class, '(');
		if ($haveArgs !== false) {
			$args = substr($class, $haveArgs+1, -1);
			$class = substr($class, 0, $haveArgs);
		}
		switch($class) {
			case 'even':
			case 'odd':
				$stack = array();
				foreach($this->elements as $i => $node) {
					if ($class == 'even' && ($i%2) == 0)
						$stack[] = $node;
					else if ( $class == 'odd' && $i % 2 )
						$stack[] = $node;
				}
				$this->elements = $stack;
				break;
			case 'eq':
				$k = intval($args);
				$this->elements = isset( $this->elements[$k] )
					? array( $this->elements[$k] )
					: array();
				break;
			case 'gt':
				$this->elements = array_slice($this->elements, $args+1);
				break;
			case 'lt':
				$this->elements = array_slice($this->elements, 0, $args+1);
				break;
			case 'first':
				if (isset($this->elements[0]))
					$this->elements = array($this->elements[0]);
				break;
			case 'last':
				if ($this->elements)
					$this->elements = array($this->elements[count($this->elements)-1]);
				break;
			/*case 'parent':
				$stack = array();
				foreach($this->elements as $node) {
					if ( $node->childNodes->length )
						$stack[] = $node;
				}
				$this->elements = $stack;
				break;*/
			case 'contains':
				$text = trim($args, "\"'");
				$stack = array();
				foreach($this->elements as $node) {
					if (mb_stripos($node->textContent, $text) === false)
						continue;
					$stack[] = $node;
				}
				$this->elements = $stack;
				break;
			case 'not':
				$selector = self::unQuote($args);
				$this->elements = $this->not($selector)->stack();
				break;
			case 'slice':
				$args = explode(',',
					str_replace(', ', ',', trim($args, "\"'"))
				);
				$start = $args[0];
				$end = isset($args[1])
					? $args[1]
					: null;
				if ($end > 0)
					$end = $end-$start;
				$this->elements = array_slice($this->elements, $start, $end);
				break;
			case 'has':
				$selector = trim($args, "\"'");
				$stack = array();
				foreach($this->stack(1) as $el) {
					if ($this->find($selector, $el, true)->length)
						$stack[] = $el;
				}
				$this->elements = $stack;
				break;
			case 'submit':
			case 'reset':
				$this->elements = ArcaelaDOM::merge(
					$this->map(array($this, 'is'),
						"input[type=$class]", new CallbackParam()
					),
					$this->map(array($this, 'is'),
						"button[type=$class]", new CallbackParam()
					)
				);
			break;
			case 'input':
				$this->elements = $this->map(
					array($this, 'is'),
					'input', new CallbackParam()
				)->elements;
			break;
			case 'password':
			case 'checkbox':
			case 'radio':
			case 'hidden':
			case 'image':
			case 'file':
				$this->elements = $this->map(
					array($this, 'is'),
					"input[type=$class]", new CallbackParam()
				)->elements;
			break;
			case 'parent':
				$this->elements = $this->map(
					create_function('$node', '
						return $node instanceof \DOMELEMENT && $node->childNodes->length
							? $node : null;')
				)->elements;
			break;
			case 'empty':
				$this->elements = $this->map(
					create_function('$node', '
						return $node instanceof \DOMELEMENT && $node->childNodes->length
							? null : $node;')
				)->elements;
			break;
			case 'disabled':
			case 'selected':
			case 'checked':
				$this->elements = $this->map(
					array($this, 'is'),
					"[$class]", new CallbackParam()
				)->elements;
			break;
			case 'enabled':
				$this->elements = $this->map(
					create_function('$node', '
						return querySelector($node)->not(":disabled") ? $node : null;')
				)->elements;
			break;
			case 'header':
				$this->elements = $this->map(
					create_function('$node',
						'$isHeader = isset($node->tagName) && in_array($node->tagName, array(
							"h1", "h2", "h3", "h4", "h5", "h6", "h7"
						));
						return $isHeader
							? $node
							: null;')
				)->elements;
			break;
			case 'only-child':
				$this->elements = $this->map(
					create_function('$node',
						'return querySelector($node)->siblings()->size() == 0 ? $node : null;')
				)->elements;
			break;
			case 'first-child':
				$this->elements = $this->map(
					create_function('$node', 'return querySelector($node)->prevAll()->size() == 0 ? $node : null;')
				)->elements;
			break;
			case 'last-child':
				$this->elements = $this->map(
					create_function('$node', 'return querySelector($node)->nextAll()->size() == 0 ? $node : null;')
				)->elements;
			break;
			case 'nth-child':
				$param = trim($args, "\"'");
				if (! $param)
					break;
				if ($param[0] == 'n')
					$param = '1'.$param;
				if ($param == 'even' || $param == 'odd')
					$mapped = $this->map(
						create_function('$node, $param',
							'$index = querySelector($node)->prevAll()->size()+1;
							if ($param == "even" && ($index%2) == 0)
								return $node;
							else if ($param == "odd" && $index%2 == 1)
								return $node;
							else
								return null;'),
						new CallbackParam(), $param
					);
				else if (mb_strlen($param) > 1 && $param[1] == 'n')
					$mapped = $this->map(
						create_function('$node, $param',
							'$prevs = querySelector($node)->prevAll()->size();
							$index = 1+$prevs;
							$b = mb_strlen($param) > 3
								? $param{3}
								: 0;
							$a = $param{0};
							if ($b && $param{2} == "-")
								$b = -$b;
							if ($a > 0) {
								return ($index-$b)%$a == 0
									? $node
									: null;
								ArcaelaDOM::debug($a."*".floor($index/$a)."+$b-1 == ".($a*floor($index/$a)+$b-1)." ?= $prevs");
								return $a*floor($index/$a)+$b-1 == $prevs
										? $node
										: null;
							} else if ($a == 0)
								return $index == $b
										? $node
										: null;
							else
								return $index <= $b
										? $node
										: null;
							'),
						new CallbackParam(), $param
					);
				else
					$mapped = $this->map(
						create_function('$node, $index',
							'$prevs = querySelector($node)->prevAll()->size();
							if ($prevs && $prevs == $index-1)
								return $node;
							else if (! $prevs && $index == 1)
								return $node;
							else
								return null;'),
						new CallbackParam(), $param
					);
				$this->elements = $mapped->elements;
			break;
			default:
				$this->debug("Unknown pseudoclass '{$class}', skipping...");
		}
	}
	/**
	 * @access private
	 */
	protected function __pseudoClassParam($paramsString) {
	}
	/**
	 * Enter description here...
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function is($selector, $nodes = null) {
		ArcaelaDOM::debug(array("Is:", $selector));
		if (! $selector)
			return false;
		$oldStack = $this->elements;
		$returnArray = false;
		if ($nodes && is_array($nodes)) {
			$this->elements = $nodes;
		} else if ($nodes)
			$this->elements = array($nodes);
		$this->filter($selector, true);
		$stack = $this->elements;
		$this->elements = $oldStack;
		if ($nodes)
			return $stack ? $stack : null;
		return (bool)count($stack);
	}
	/**
	 * Enter description here...
	 * jQuery difference.
	 *
	 * Callback:
	 * - $index int
	 * - $node \DOMNode
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 * @link http://docs.jquery.com/Traversing/filter
	 */
	public function filterCallback($callback, $_skipHistory = false) {
		if (! $_skipHistory) {
			$this->elementsBackup = $this->elements;
			$this->debug("Filtering by callback");
		}
		$newStack = array();
		foreach($this->elements as $index => $node) {
			$result = ArcaelaDOM::callbackRun($callback, array($index, $node));
			if (is_null($result) || (! is_null($result) && $result))
				$newStack[] = $node;
		}
		$this->elements = $newStack;
		return $_skipHistory
			? $this
			: $this->newInstance();
	}
	/**
	 * Enter description here...
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 * @link http://docs.jquery.com/Traversing/filter
	 */
	public function filter($selectors, $_skipHistory = false) {
		if ($selectors instanceof Callback OR $selectors instanceof \Closure)
			return $this->filterCallback($selectors, $_skipHistory);
		if (! $_skipHistory)
			$this->elementsBackup = $this->elements;
		$notSimpleSelector = array(' ', '>', '~', '+', '/');
		if (! is_array($selectors))
			$selectors = $this->parseSelector($selectors);
		if (! $_skipHistory)
			$this->debug(array("Filtering:", $selectors));
		$finalStack = array();
		foreach($selectors as $selector) {
			$stack = array();
			if (! $selector)
				break;
			if (in_array($selector[0], $notSimpleSelector))
				$selector = array_slice($selector, 1);
			foreach($this->stack() as $node) {
				$break = false;
				foreach($selector as $s) {
					if (!($node instanceof \DOMELEMENT)) {
						if ( $s[0] == '[') {
							$attr = trim($s, '[]');
							if ( mb_strpos($attr, '=')) {
								list( $attr, $val ) = explode('=', $attr);
								if ($attr == 'nodeType' && $node->nodeType != $val)
									$break = true;
							}
						} else
							$break = true;
					} else {
						if ( $s[0] == '#') {
							if ( $node->getAttribute('id') != substr($s, 1) )
								$break = true;
						} else if ( $s[0] == '.') {
							if (! $this->matchClasses( $s, $node ) )
								$break = true;
						} else if ( $s[0] == '[') {
							$attr = trim($s, '[]');
							if (mb_strpos($attr, '=')) {
								list($attr, $val) = explode('=', $attr);
								$val = self::unQuote($val);
								if ($attr == 'nodeType') {
									if ($val != $node->nodeType)
										$break = true;
								} else if ($this->isRegexp($attr)) {
									$val = extension_loaded('mbstring') && ArcaelaDOM::$mbstringSupport
										? quotemeta(trim((string) $val, '"\''))
										: preg_quote(trim((string) $val, '"\''), '@');
									switch( substr($attr, -1)) {
										case '^':
											$pattern = '^'.$val;
											break;
										case '*':
											$pattern = '.*'.$val.'.*';
											break;
										case '$':
											$pattern = '.*'.$val.'$';
											break;
									}
									$attr = substr($attr, 0, -1);
									$isMatch = extension_loaded('mbstring') && ArcaelaDOM::$mbstringSupport
										? mb_ereg_match($pattern, $node->getAttribute($attr))
										: preg_match("@{$pattern}@", $node->getAttribute($attr));
									if (! $isMatch)
										$break = true;
								} else if ($node->getAttribute($attr) != $val)
									$break = true;
							} else if (! $node->hasAttribute($attr))
								$break = true;
						} else if ( $s[0] == ':') {
						} else if (trim($s)) {
							if ($s != '*') {
								if (isset($node->tagName)) {
									if ($node->tagName != $s)
										$break = true;
								} else if ($s == 'html' && ! $this->isRoot($node))
									$break = true;
							}
						} else if (in_array($s, $notSimpleSelector)) {
							$break = true;
							$this->debug(array('Skipping non simple selector', $selector));
						}
					}
					if ($break)
						break;
				}
				if (! $break )
					$stack[] = $node;
			}
			$tmpStack = $this->elements;
			$this->elements = $stack;
			foreach($selector as $s)
				if ($s[0] == ':')
					$this->pseudoClasses($s);
			foreach($this->elements as $node)
				$finalStack[] = $node;
			$this->elements = $tmpStack;
		}
		$this->elements = $finalStack;
		if ($_skipHistory) {
			return $this;
		} else {
			$this->debug("Stack length after filter(): ".count($finalStack));
			return $this->newInstance();
		}
	}
	/**
	 *
	 * @param $value
	 * @return unknown_type
	 * @TODO implement in all methods using passed parameters
	 */
	protected static function unQuote($value) {
		return $value[0] == '\'' || $value[0] == '"'
			? substr($value, 1, -1)
			: $value;
	}
	/**
	 * Enter description here...
	 *
	 * @link http://docs.jquery.com/Ajax/load
	 * @return ArcaelaDOM|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 * @todo Support $selector
	 */
	public function load($url, $data = null, $callback = null) {
		if ($data && ! is_array($data)) {
			$callback = $data;
			$data = null;
		}
		if (mb_strpos($url, ' ') !== false) {
			$matches = null;
			if (extension_loaded('mbstring') && ArcaelaDOM::$mbstringSupport)
				mb_ereg('^([^ ]+) (.*)$', $url, $matches);
			else
				preg_match('^([^ ]+) (.*)$', $url, $matches);
			$url = $matches[1];
			$selector = $matches[2];
			$this->_loadSelector = $selector;
		}
		$ajax = array(
			'url' => $url,
			'type' => $data ? 'POST' : 'GET',
			'data' => $data,
			'complete' => $callback,
			'success' => array($this, '__loadSuccess')
		);
		ArcaelaDOM::ajax($ajax);
		return $this;
	}
	/**
	 * @access private
	 * @param $html
	 * @return unknown_type
	 */
	public function __loadSuccess($html) {
		if ($this->_loadSelector) {
			$html = ArcaelaDOM::newDocument($html)->find($this->_loadSelector);
			unset($this->_loadSelector);
		}
		foreach($this->stack(1) as $node) {
			ArcaelaDOM::querySelector($node, $this->getDocumentID())
				->markup($html);
		}
	}
	/**
	 * Enter description here...
	 *
	 * @return ArcaelaDOM|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 * @todo
	 */
	public function css() {
		return $this;
	}
	/**
	 * @todo
	 *
	 */
	public function show(){
		return $this;
	}
	/**
	 * @todo
	 *
	 */
	public function hide(){
		return $this;
	}
	/**
	 * Trigger a type of event on every matched element.
	 *
	 * @param unknown_type $type
	 * @param unknown_type $data
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 * @TODO support more than event in $type (space-separated)
	 */
	public function trigger($type, $data = array()) {
		foreach($this->elements as $node)
			ArcaelaDOMEvents::trigger($this->getDocumentID(), $type, $data, $node);
		return $this;
	}
	/**
	 * This particular method triggers all bound event handlers on an element (for a specific event type) WITHOUT executing the browsers default actions.
	 *
	 * @param unknown_type $type
	 * @param unknown_type $data
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 * @TODO
	 */
	public function triggerHandler($type, $data = array()) {
	}
	/**
	 * Binds a handler to one or more events (like click) for each matched element.
	 * Can also bind custom events.
	 *
	 * @param unknown_type $type
	 * @param unknown_type $data Optional
	 * @param unknown_type $callback
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 * @TODO support '!' (exclusive) events
	 * @TODO support more than event in $type (space-separated)
	 */
	public function bind($type, $data, $callback = null) {
		if (! isset($callback)) {
			$callback = $data;
			$data = null;
		}
		foreach($this->elements as $node)
			ArcaelaDOMEvents::add($this->getDocumentID(), $node, $type, $data, $callback);
		return $this;
	}
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $type
	 * @param unknown_type $callback
	 * @return unknown
	 * @TODO namespace events
	 * @TODO support more than event in $type (space-separated)
	 */
	public function unbind($type = null, $callback = null) {
		foreach($this->elements as $node)
			ArcaelaDOMEvents::remove($this->getDocumentID(), $node, $type, $callback);
		return $this;
	}
	/**
	 * Enter description here...
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function change($callback = null) {
		if ($callback)
			return $this->bind('change', $callback);
		return $this->trigger('change');
	}
	/**
	 * Enter description here...
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function submit($callback = null) {
		if ($callback)
			return $this->bind('submit', $callback);
		return $this->trigger('submit');
	}
	/**
	 * Enter description here...
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function click($callback = null) {
		if ($callback)
			return $this->bind('click', $callback);
		return $this->trigger('click');
	}
	/**
	 * Enter description here...
	 *
	 * @param String|ArcaelaDOM
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function wrapAllOld($wrapper) {
		$wrapper = querySelector($wrapper)->_clone();
		if (! $wrapper->length || ! $this->length )
			return $this;
		$wrapper->insertBefore($this->elements[0]);
		$deepest = $wrapper->elements[0];
		while($deepest->firstChild && $deepest->firstChild instanceof \DOMELEMENT)
			$deepest = $deepest->firstChild;
		querySelector($deepest)->append($this);
		return $this;
	}
	/**
	 * Enter description here...
	 *
	 * TODO testme...
	 * @param String|ArcaelaDOM
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function wrapAll($wrapper) {
		if (! $this->length)
			return $this;
		return ArcaelaDOM::querySelector($wrapper, $this->getDocumentID())
			->clone()
			->insertBefore($this->get(0))
			->map(array($this, '___wrapAllCallback'))
			->append($this);
	}
  /**
   *
	 * @param $node
	 * @return unknown_type
	 * @access private
   */
	public function ___wrapAllCallback($node) {
		$deepest = $node;
		while($deepest->firstChild && $deepest->firstChild instanceof \DOMELEMENT)
			$deepest = $deepest->firstChild;
		return $deepest;
	}
	/**
	 * Enter description here...
	 * NON JQUERY METHOD
	 *
	 * @param String|ArcaelaDOM
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function wrapAllPHP($codeBefore, $codeAfter) {
		return $this
			->slice(0, 1)
				->beforePHP($codeBefore)
			->end()
			->slice(-1)
				->afterPHP($codeAfter)
			->end();
	}
	/**
	 * Enter description here...
	 *
	 * @param String|ArcaelaDOM
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function wrap($wrapper) {
		foreach($this->stack() as $node)
			ArcaelaDOM::querySelector($node, $this->getDocumentID())->wrapAll($wrapper);
		return $this;
	}
	/**
	 * Enter description here...
	 *
	 * @param String|ArcaelaDOM
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function wrapPHP($codeBefore, $codeAfter) {
		foreach($this->stack() as $node)
			ArcaelaDOM::querySelector($node, $this->getDocumentID())->wrapAllPHP($codeBefore, $codeAfter);
		return $this;
	}
	/**
	 * Enter description here...
	 *
	 * @param String|ArcaelaDOM
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function wrapInner($wrapper) {
		foreach($this->stack() as $node)
			ArcaelaDOM::querySelector($node, $this->getDocumentID())->contents()->wrapAll($wrapper);
		return $this;
	}
	/**
	 * Enter description here...
	 *
	 * @param String|ArcaelaDOM
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function wrapInnerPHP($codeBefore, $codeAfter) {
		foreach($this->stack(1) as $node)
			ArcaelaDOM::querySelector($node, $this->getDocumentID())->contents()
				->wrapAllPHP($codeBefore, $codeAfter);
		return $this;
	}
	/**
	 * Enter description here...
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 * @testme Support for text nodes
	 */
	public function contents() {
		$stack = array();
		foreach($this->stack(1) as $el) {
			foreach($el->childNodes as $node) {
				$stack[] = $node;
			}
		}
		return $this->newInstance($stack);
	}
	/**
	 * Enter description here...
	 *
	 * jQuery difference.
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function contentsUnwrap() {
		foreach($this->stack(1) as $node) {
			if (! $node->parentNode )
				continue;
			$childNodes = array();
			foreach($node->childNodes as $chNode )
				$childNodes[] = $chNode;
			foreach($childNodes as $chNode )
				$node->parentNode->insertBefore($chNode, $node);
			$node->parentNode->removeChild($node);
		}
		return $this;
	}
	/**
	 * Enter description here...
	 *
	 * jQuery difference.
	 */
	public function switchWith($markup) {
		$markup = querySelector($markup, $this->getDocumentID());
		$content = null;
		foreach($this->stack(1) as $node) {
			querySelector($node)
				->contents()->toReference($content)->end()
				->replaceWith($markup->clone()->append($content));
		}
		return $this;
	}
	/**
	 * Enter description here...
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function eq($num) {
		$oldStack = $this->elements;
		$this->elementsBackup = $this->elements;
		$this->elements = array();
		if ( isset($oldStack[$num]) )
			$this->elements[] = $oldStack[$num];
		return $this->newInstance();
	}
	/**
	 * Enter description here...
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function size() {
		return count($this->elements);
	}
	/**
	 * Enter description here...
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 * @deprecated Use length as attribute
	 */
	public function length() {
		return $this->size();
	}
	public function count() {
		return $this->size();
	}
	/**
	 * Enter description here...
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 * @todo $level
	 */
	public function end($level = 1) {
		return $this->previous
			? $this->previous
			: $this;
	}
	/**
	 * Enter description here...
	 * Normal use ->clone() .
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 * @access private
	 */
	public function _clone() {
		$newStack = array();
		$this->elementsBackup = $this->elements;
		foreach($this->elements as $node) {
			$newStack[] = $node->cloneNode(true);
		}
		$this->elements = $newStack;
		return $this->newInstance();
	}
	/**
	 * Enter description here...
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function replaceWithPHP($code) {
		return $this->replaceWith(ArcaelaDOM::php($code));
	}
	/**
	 * Enter description here...
	 *
	 * @param String|ArcaelaDOM $content
	 * @link http://docs.jquery.com/Manipulation/replaceWith#content
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function replaceWith($content) {
		return $this->after($content)->remove();
	}
	/**
	 * Enter description here...
	 *
	 * @param String $selector
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 * @todo this works ?
	 */
	public function replaceAll($selector) {
		foreach(ArcaelaDOM::querySelector($selector, $this->getDocumentID()) as $node)
			ArcaelaDOM::querySelector($node, $this->getDocumentID())
				->after($this->_clone())
				->remove();
		return $this;
	}
	/**
	 * Enter description here...
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function remove($selector = null) {
		$loop = $selector
			? $this->filter($selector)->elements
			: $this->elements;
		foreach($loop as $node) {
			if (! $node->parentNode )
				continue;
			if (isset($node->tagName))
				$this->debug("Removing '{$node->tagName}'");
			$node->parentNode->removeChild($node);
			$event = new DOMEvent(array(
				'target' => $node,
				'type' => 'DOMNodeRemoved'
			));
			ArcaelaDOMEvents::trigger($this->getDocumentID(),
				$event->type, array($event), $node
			);
		}
		return $this;
	}
	protected function markupEvents($newMarkup, $oldMarkup, $node) {
		if ($node->tagName == 'textarea' && $newMarkup != $oldMarkup) {
			$event = new DOMEvent(array(
				'target' => $node,
				'type' => 'change'
			));
			ArcaelaDOMEvents::trigger($this->getDocumentID(),
				$event->type, array($event), $node
			);
		}
	}
	/**
	 * jQuey difference
	 *
	 * @param $markup
	 * @return unknown_type
	 * @TODO trigger change event for textarea
	 */
	public function markup($markup = null, $callback1 = null, $callback2 = null, $callback3 = null) {
		$args = func_get_args();
		if ($this->documentWrapper->isXML)
			return call_user_func_array(array($this, 'xml'), $args);
		else
			return call_user_func_array(array($this, 'html'), $args);
	}
	/**
	 * jQuey difference
	 *
	 * @param $markup
	 * @return unknown_type
	 */
	public function markupOuter($callback1 = null, $callback2 = null, $callback3 = null) {
		$args = func_get_args();
		if ($this->documentWrapper->isXML)
			return call_user_func_array(array($this, 'xmlOuter'), $args);
		else
			return call_user_func_array(array($this, 'htmlOuter'), $args);
	}
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $html
	 * @return string|ArcaelaDOM|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 * @TODO force html result
	 */
	public function html($html = null, $callback1 = null, $callback2 = null, $callback3 = null) {
		if (isset($html)) {
			$nodes = $this->documentWrapper->import($html);
			$this->empty();
			foreach($this->stack(1) as $alreadyAdded => $node) {
				if (($this->isXHTML() || $this->isHTML()) && $node->tagName == 'textarea')
					$oldHtml = querySelector($node, $this->getDocumentID())->markup();
				foreach($nodes as $newNode) {
					$node->appendChild($alreadyAdded
						? $newNode->cloneNode(true)
						: $newNode
					);
				}
				if (($this->isXHTML() || $this->isHTML()) && $node->tagName == 'textarea')
					$this->markupEvents($html, $oldHtml, $node);
			}
			return $this;
		} else {
			$return = $this->documentWrapper->markup($this->elements, true);
			$args = func_get_args();
			foreach(array_slice($args, 1) as $callback) {
				$return = ArcaelaDOM::callbackRun($callback, array($return));
			}
			return $return;
		}
	}
	/**
	 * @TODO force xml result
	 */
	public function xml($xml = null, $callback1 = null, $callback2 = null, $callback3 = null) {
		$args = func_get_args();
		return call_user_func_array(array($this, 'html'), $args);
	}
	/**
	 * Enter description here...
	 * @TODO force html result
	 *
	 * @return String
	 */
	public function htmlOuter($callback1 = null, $callback2 = null, $callback3 = null) {
		$markup = $this->documentWrapper->markup($this->elements);
		$args = func_get_args();
		foreach($args as $callback) {
			$markup = ArcaelaDOM::callbackRun($callback, array($markup));
		}
		return $markup;
	}
	/**
	 * @TODO force xml result
	 */
	public function xmlOuter($callback1 = null, $callback2 = null, $callback3 = null) {
		$args = func_get_args();
		return call_user_func_array(array($this, 'htmlOuter'), $args);
	}
	public function __toString() {
		return $this->markupOuter();
	}
	/**
	 * Just like html(), but returns markup with VALID (dangerous) PHP tags.
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 * @todo support returning markup with PHP tags when called without param
	 */
	public function php($code = null) {
		return $this->markupPHP($code);
	}
	/**
	 * Enter description here...
	 * 
	 * @param $code
	 * @return unknown_type
	 */
	public function markupPHP($code = null) {
		return isset($code)
			? $this->markup(ArcaelaDOM::php($code))
			: ArcaelaDOM::markupToPHP($this->markup());
	}
	/**
	 * Enter description here...
	 * 
	 * @param $code
	 * @return unknown_type
	 */
	public function markupOuterPHP() {
		return ArcaelaDOM::markupToPHP($this->markupOuter());
	}
	/**
	 * Enter description here...
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function children($selector = null) {
		$stack = array();
		foreach($this->stack(1) as $node) {
			foreach($node->childNodes as $newNode) {
				if ($newNode->nodeType != 1)
					continue;
				if ($selector && ! $this->is($selector, $newNode))
					continue;
				if ($this->elementsContainsNode($newNode, $stack))
					continue;
				$stack[] = $newNode;
			}
		}
		$this->elementsBackup = $this->elements;
		$this->elements = $stack;
		return $this->newInstance();
	}
	/**
	 * Enter description here...
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function ancestors($selector = null) {
		return $this->children( $selector );
	}
	/**
	 * Enter description here...
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function append( $content) {
		return $this->insert($content, __FUNCTION__);
	}
	/**
	 * Enter description here...
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function appendPHP( $content) {
		return $this->insert("<php><!-- {$content} --></php>", 'append');
	}
	/**
	 * Enter description here...
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function appendTo( $seletor) {
		return $this->insert($seletor, __FUNCTION__);
	}
	/**
	 * Enter description here...
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function prepend( $content) {
		return $this->insert($content, __FUNCTION__);
	}
	/**
	 * Enter description here...
	 *
	 * @todo accept many arguments, which are joined, arrays maybe also
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function prependPHP( $content) {
		return $this->insert("<php><!-- {$content} --></php>", 'prepend');
	}
	/**
	 * Enter description here...
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function prependTo( $seletor) {
		return $this->insert($seletor, __FUNCTION__);
	}
	/**
	 * Enter description here...
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function before($content) {
		return $this->insert($content, __FUNCTION__);
	}
	/**
	 * Enter description here...
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function beforePHP( $content) {
		return $this->insert("<php><!-- {$content} --></php>", 'before');
	}
	/**
	 * Enter description here...
	 *
	 * @param String|ArcaelaDOM
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function insertBefore( $seletor) {
		return $this->insert($seletor, __FUNCTION__);
	}
	/**
	 * Enter description here...
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function after( $content) {
		return $this->insert($content, __FUNCTION__);
	}
	/**
	 * Enter description here...
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function afterPHP( $content) {
		return $this->insert("<php><!-- {$content} --></php>", 'after');
	}
	/**
	 * Enter description here...
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function insertAfter( $seletor) {
		return $this->insert($seletor, __FUNCTION__);
	}
	/**
	 * Internal insert method. Don't use it.
	 *
	 * @param unknown_type $target
	 * @param unknown_type $type
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 * @access private
	 */
	public function insert($target, $type) {
		$this->debug("Inserting data with '{$type}'");
		$to = false;
		switch( $type) {
			case 'appendTo':
			case 'prependTo':
			case 'insertBefore':
			case 'insertAfter':
				$to = true;
		}
		switch(gettype($target)) {
			case 'string':
				$insertFrom = $insertTo = array();
				if ($to) {
					$insertFrom = $this->elements;
					if (ArcaelaDOM::isMarkup($target)) {
						$insertTo = $this->documentWrapper->import($target);
					} else {
						$thisStack = $this->elements;
						$this->toRoot();
						$insertTo = $this->find($target)->elements;
						$this->elements = $thisStack;
					}
				} else {
					$insertTo = $this->elements;
					$insertFrom = $this->documentWrapper->import($target);
				}
				break;
			case 'object':
				$insertFrom = $insertTo = array();
				if ($target instanceof self) {
					if ($to) {
						$insertTo = $target->elements;
						if ($this->documentFragment && $this->stackIsRoot())
							$loop = $this->root->childNodes;
						else
							$loop = $this->elements;
						$insertFrom = $this->getDocumentID() == $target->getDocumentID()
							? $loop
							: $target->documentWrapper->import($loop);
					} else {
						$insertTo = $this->elements;
						if ( $target->documentFragment && $target->stackIsRoot() )
							$loop = $target->root->childNodes;
						else
							$loop = $target->elements;
						$insertFrom = $this->getDocumentID() == $target->getDocumentID()
							? $loop
							: $this->documentWrapper->import($loop);
					}
				} elseif ($target instanceof \DOMNODE) {
					if ( $to) {
						$insertTo = array($target);
						if ($this->documentFragment && $this->stackIsRoot())
							$loop = $this->root->childNodes;
						else
							$loop = $this->elements;
						foreach($loop as $fromNode)
							$insertFrom[] = ! $fromNode->ownerDocument->isSameNode($target->ownerDocument)
								? $target->ownerDocument->importNode($fromNode, true)
								: $fromNode;
					} else {
						if (! $target->ownerDocument->isSameNode($this->document))
							$target = $this->document->importNode($target, true);
						$insertTo = $this->elements;
						$insertFrom[] = $target;
					}
				}
				break;
		}
		ArcaelaDOM::debug("From ".count($insertFrom)."; To ".count($insertTo)." nodes");
		foreach($insertTo as $insertNumber => $toNode) {
			switch( $type) {
				case 'prependTo':
				case 'prepend':
					$firstChild = $toNode->firstChild;
					break;
				case 'insertAfter':
				case 'after':
					$nextSibling = $toNode->nextSibling;
					break;
			}
			foreach($insertFrom as $fromNode) {
				$insert = $insertNumber
					? $fromNode->cloneNode(true)
					: $fromNode;
				switch($type) {
					case 'appendTo':
					case 'append':
						$toNode->appendChild($insert);
						$eventTarget = $insert;
						break;
					case 'prependTo':
					case 'prepend':
						$toNode->insertBefore(
							$insert,
							$firstChild
						);
						break;
					case 'insertBefore':
					case 'before':
						if (! $toNode->parentNode)
							throw new \Exception("No parentNode, can't do {$type}()");
						else
							$toNode->parentNode->insertBefore(
								$insert,
								$toNode
							);
						break;
					case 'insertAfter':
					case 'after':
						if (! $toNode->parentNode)
							throw new \Exception("No parentNode, can't do {$type}()");
						else
							$toNode->parentNode->insertBefore(
								$insert,
								$nextSibling
							);
						break;
				}
				$event = new DOMEvent(array(
					'target' => $insert,
					'type' => 'DOMNodeInserted'
				));
				ArcaelaDOMEvents::trigger($this->getDocumentID(),
					$event->type, array($event), $insert
				);
			}
		}
		return $this;
	}
	/**
	 * Enter description here...
	 *
	 * @return Int
	 */
	public function index($subject) {
		$index = -1;
		$subject = $subject instanceof ArcaelaElement
			? $subject->elements[0]
			: $subject;
		foreach($this->newInstance() as $k => $node) {
			if ($node->isSameNode($subject))
				$index = $k;
		}
		return $index;
	}
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $start
	 * @param unknown_type $end
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 * @testme
	 */
	public function slice($start, $end = null) {
		if ($end > 0)
			$end = $end - (int) $start;
		return $this->newInstance(
			array_slice($this->elements, (int) $start, $end)
		);
	}
	/**
	 * Enter description here...
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function reverse() {
		$this->elementsBackup = $this->elements;
		$this->elements = array_reverse($this->elements);
		return $this->newInstance();
	}
	/**
	 * Return joined text content.
	 * @return String
	 */
	public function text($text = null, $callback1 = null, $callback2 = null, $callback3 = null) {
		if (isset($text))
			return $this->html(htmlspecialchars($text));
		$args = func_get_args();
		$args = array_slice($args, 1);
		$return = '';
		foreach($this->elements as $node) {
			$text = $node->textContent;
			if (count($this->elements) > 1 && $text)
				$text .= "\n";
			foreach($args as $callback) {
				$text = ArcaelaDOM::callbackRun($callback, array($text));
			}
			$return .= $text;
		}
		return $return;
	}
	/**
	 * Enter description here...
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function plugin($class, $file = null) {
		ArcaelaDOM::plugin($class, $file);
		return $this;
	}
	/**
	 *
	 * @access private
	 * @param $method
	 * @param $args
	 * @return unknown_type
	 */
	public function __call($method, $args) {
		$aliasMethods = array('clone', 'empty');
		if (isset(ArcaelaDOM::$extendMethods[$method])) {
			array_unshift($args, $this);
			return ArcaelaDOM::callbackRun(
				ArcaelaDOM::$extendMethods[$method], $args
			);
		} else if (isset(ArcaelaDOM::$pluginsMethods[$method])) {
			array_unshift($args, $this);
			$class = ArcaelaDOM::$pluginsMethods[$method];
			$realClass = "ArcaelaElementPlugin_$class";
			$return = call_user_func_array(
				array($realClass, $method),
				$args
			);
			return is_null($return)
				? $this
				: $return;
		} else if (in_array($method, $aliasMethods)) {
			return call_user_func_array(array($this, '_'.$method), $args);
		} else
			throw new \Exception("Method '{$method}' doesnt exist");
	}
	/**
	 * Safe rename of next().
	 *
	 * Use it ONLY when need to call next() on an iterated object (in same time).
	 * Normaly there is no need to do such thing ;)
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 * @access private
	 */
	public function _next($selector = null) {
		return $this->newInstance(
			$this->getElementSiblings('nextSibling', $selector, true)
		);
	}
	/**
	 * Use prev() and next().
	 *
	 * @deprecated
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 * @access private
	 */
	public function _prev($selector = null) {
		return $this->prev($selector);
	}
	/**
	 * Enter description here...
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function prev($selector = null) {
		return $this->newInstance(
			$this->getElementSiblings('previousSibling', $selector, true)
		);
	}
	/**
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 * @todo
	 */
	public function prevAll($selector = null) {
		return $this->newInstance(
			$this->getElementSiblings('previousSibling', $selector)
		);
	}
	/**
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 * @todo FIXME: returns source elements insted of next siblings
	 */
	public function nextAll($selector = null) {
		return $this->newInstance(
			$this->getElementSiblings('nextSibling', $selector)
		);
	}
	/**
	 * @access private
	 */
	protected function getElementSiblings($direction, $selector = null, $limitToOne = false) {
		$stack = array();
		$count = 0;
		foreach($this->stack() as $node) {
			$test = $node;
			while( isset($test->{$direction}) && $test->{$direction}) {
				$test = $test->{$direction};
				if (! $test instanceof \DOMELEMENT)
					continue;
				$stack[] = $test;
				if ($limitToOne)
					break;
			}
		}
		if ($selector) {
			$stackOld = $this->elements;
			$this->elements = $stack;
			$stack = $this->filter($selector, true)->stack();
			$this->elements = $stackOld;
		}
		return $stack;
	}
	/**
	 * Enter description here...
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function siblings($selector = null) {
		$stack = array();
		$siblings = array_merge(
			$this->getElementSiblings('previousSibling', $selector),
			$this->getElementSiblings('nextSibling', $selector)
		);
		foreach($siblings as $node) {
			if (! $this->elementsContainsNode($node, $stack))
				$stack[] = $node;
		}
		return $this->newInstance($stack);
	}
	/**
	 * Enter description here...
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function not($selector = null) {
		if (is_string($selector))
			ArcaelaDOM::debug(array('not', $selector));
		else
			ArcaelaDOM::debug('not');
		$stack = array();
		if ($selector instanceof self || $selector instanceof \DOMNODE) {
			foreach($this->stack() as $node) {
				if ($selector instanceof self) {
					$matchFound = false;
					foreach($selector->stack() as $notNode) {
						if ($notNode->isSameNode($node))
							$matchFound = true;
					}
					if (! $matchFound)
						$stack[] = $node;
				} else if ($selector instanceof \DOMNODE) {
					if (! $selector->isSameNode($node))
						$stack[] = $node;
				} else {
					if (! $this->is($selector))
						$stack[] = $node;
				}
			}
		} else {
			$orgStack = $this->stack();
			$matched = $this->filter($selector, true)->stack();
			foreach($orgStack as $node)
				if (! $this->elementsContainsNode($node, $matched))
					$stack[] = $node;
		}
		return $this->newInstance($stack);
	}
	/**
	 * Enter description here...
	 *
	 * @param string|ArcaelaElement
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function add($selector = null) {
		if (! $selector)
			return $this;
		$stack = array();
		$this->elementsBackup = $this->elements;
		$found = ArcaelaDOM::querySelector($selector, $this->getDocumentID());
		$this->merge($found->elements);
		return $this->newInstance();
	}
	/**
	 * @access private
	 */
	protected function merge() {
		foreach(func_get_args() as $nodes)
			foreach($nodes as $newNode )
				if (! $this->elementsContainsNode($newNode) )
					$this->elements[] = $newNode;
	}
	/**
	 * @access private
	 * TODO refactor to stackContainsNode
	 */
	protected function elementsContainsNode($nodeToCheck, $elementsStack = null) {
		$loop = ! is_null($elementsStack)
			? $elementsStack
			: $this->elements;
		foreach($loop as $node) {
			if ( $node->isSameNode( $nodeToCheck ) )
				return true;
		}
		return false;
	}
	/**
	 * Enter description here...
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function parent($selector = null) {
		$stack = array();
		foreach($this->elements as $node )
			if ( $node->parentNode && ! $this->elementsContainsNode($node->parentNode, $stack) )
				$stack[] = $node->parentNode;
		$this->elementsBackup = $this->elements;
		$this->elements = $stack;
		if ( $selector )
			$this->filter($selector, true);
		return $this->newInstance();
	}
	/**
	 * Enter description here...
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function parents($selector = null) {
		$stack = array();
		if (! $this->elements )
			$this->debug('parents() - stack empty');
		foreach($this->elements as $node) {
			$test = $node;
			while( $test->parentNode) {
				$test = $test->parentNode;
				if ($this->isRoot($test))
					break;
				if (! $this->elementsContainsNode($test, $stack)) {
					$stack[] = $test;
					continue;
				}
			}
		}
		$this->elementsBackup = $this->elements;
		$this->elements = $stack;
		if ( $selector )
			$this->filter($selector, true);
		return $this->newInstance();
	}
	/**
	 * Internal stack iterator.
	 *
	 * @access private
	 */
	public function stack($nodeTypes = null) {
		if (!isset($nodeTypes))
			return $this->elements;
		if (!is_array($nodeTypes))
			$nodeTypes = array($nodeTypes);
		$return = array();
		foreach($this->elements as $node) {
			if (in_array($node->nodeType, $nodeTypes))
				$return[] = $node;
		}
		return $return;
	}
	protected function attrEvents($attr, $oldAttr, $oldValue, $node) {
		if (! $this->isXHTML() && ! $this->isHTML())
			return;
		$event = null;
		$isInputValue = $node->tagName == 'input'
			&& (
				in_array($node->getAttribute('type'),
					array('text', 'password', 'hidden'))
				|| !$node->getAttribute('type')
				 );
		$isRadio = $node->tagName == 'input'
			&& $node->getAttribute('type') == 'radio';
		$isCheckbox = $node->tagName == 'input'
			&& $node->getAttribute('type') == 'checkbox';
		$isOption = $node->tagName == 'option';
		if ($isInputValue && $attr == 'value' && $oldValue != $node->getAttribute($attr)) {
			$event = new DOMEvent(array(
				'target' => $node,
				'type' => 'change'
			));
		} else if (($isRadio || $isCheckbox) && $attr == 'checked' && (
				(! $oldAttr && $node->hasAttribute($attr))
				|| (! $node->hasAttribute($attr) && $oldAttr)
			)) {
			$event = new DOMEvent(array(
				'target' => $node,
				'type' => 'change'
			));
		} else if ($isOption && $node->parentNode && $attr == 'selected' && (
				(! $oldAttr && $node->hasAttribute($attr))
				|| (! $node->hasAttribute($attr) && $oldAttr)
			)) {
			$event = new DOMEvent(array(
				'target' => $node->parentNode,
				'type' => 'change'
			));
		}
		if ($event) {
			ArcaelaDOMEvents::trigger($this->getDocumentID(),
				$event->type, array($event), $node
			);
		}
	}
	public function attr($attr = null, $value = null) {
		foreach($this->stack(1) as $node) {
			if (! is_null($value)) {
				$loop = $attr == '*'
					? $this->getNodeAttrs($node)
					: array($attr);
				foreach($loop as $a) {
					$oldValue = $node->getAttribute($a);
					$oldAttr = $node->hasAttribute($a);
					@$node->setAttribute($a, $value);
					$this->attrEvents($a, $oldAttr, $oldValue, $node);
				}
			} else if ($attr == '*') {
				$return = array();
				foreach($node->attributes as $n => $v)
					$return[$n] = $v->value;
				return $return;
			} else
				return $node->hasAttribute($attr)
					? $node->getAttribute($attr)
					: null;
		}
		return is_null($value)
			? '' : $this;
	}
	/**
	 * @access private
	 */
	protected function getNodeAttrs($node) {
		$return = array();
		foreach($node->attributes as $n => $o)
			$return[] = $n;
		return $return;
	}
	/**
	 * Enter description here...
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 * @todo check CDATA ???
	 */
	public function attrPHP($attr, $code) {
		if (! is_null($code)) {
			$value = '<'.'?php '.$code.' ?'.'>';
		}
		foreach($this->stack(1) as $node) {
			if (! is_null($code)) {
				$node->setAttribute($attr, $value);
			} else if ( $attr == '*') {
				$return = array();
				foreach($node->attributes as $n => $v)
					$return[$n] = $v->value;
				return $return;
			} else
				return $node->getAttribute($attr);
		}
		return $this;
	}
	/**
	 * Enter description here...
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function removeAttr($attr) {
		foreach($this->stack(1) as $node) {
			$loop = $attr == '*'
				? $this->getNodeAttrs($node)
				: array($attr);
			foreach($loop as $a) {
				$oldValue = $node->getAttribute($a);
				$node->removeAttribute($a);
				$this->attrEvents($a, $oldValue, null, $node);
			}
		}
		return $this;
	}
	/**
	 * Return form element value.
	 *
	 * @return String Fields value.
	 */
	public function val($val = null) {
		if (! isset($val)) {
			if ($this->eq(0)->is('select')) {
					$selected = $this->eq(0)->find('option[selected=selected]');
					if ($selected->is('[value]'))
						return $selected->attr('value');
					else
						return $selected->text();
			} else if ($this->eq(0)->is('textarea'))
					return $this->eq(0)->markup();
				else
					return $this->eq(0)->attr('value');
		} else {
			$_val = null;
			foreach($this->stack(1) as $node) {
				$node = querySelector($node, $this->getDocumentID());
				if (is_array($val) && in_array($node->attr('type'), array('checkbox', 'radio'))) {
					$isChecked = in_array($node->attr('value'), $val)
							|| in_array($node->attr('name'), $val);
					if ($isChecked)
						$node->attr('checked', 'checked');
					else
						$node->removeAttr('checked');
				} else if ($node->get(0)->tagName == 'select') {
					if (! isset($_val)) {
						$_val = array();
						if (! is_array($val))
							$_val = array((string)$val);
						else
							foreach($val as $v)
								$_val[] = $v;
					}
					foreach($node['option']->stack(1) as $option) {
						$option = querySelector($option, $this->getDocumentID());
						$selected = false;
						$selected = is_null($option->attr('value'))
							? in_array($option->markup(), $_val)
							: in_array($option->attr('value'), $_val);
						if ($selected)
							$option->attr('selected', 'selected');
						else
							$option->removeAttr('selected');
					}
				} else if ($node->get(0)->tagName == 'textarea')
					$node->markup($val);
				else
					$node->attr('value', $val);
			}
		}
		return $this;
	}
	/**
	 * Enter description here...
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function andSelf() {
		if ( $this->previous )
			$this->elements = array_merge($this->elements, $this->previous->elements);
		return $this;
	}
	/**
	 * Enter description here...
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function addClass( $className) {
		if (! $className)
			return $this;
		foreach($this->stack(1) as $node) {
			if (! $this->is(".$className", $node))
				$node->setAttribute(
					'class',
					trim($node->getAttribute('class').' '.$className)
				);
		}
		return $this;
	}
	/**
	 * Enter description here...
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function addClassPHP( $className) {
		foreach($this->stack(1) as $node) {
				$classes = $node->getAttribute('class');
				$newValue = $classes
					? $classes.' <'.'?php '.$className.' ?'.'>'
					: '<'.'?php '.$className.' ?'.'>';
				$node->setAttribute('class', $newValue);
		}
		return $this;
	}
	/**
	 * Enter description here...
	 *
	 * @param	string	$className
	 * @return	bool
	 */
	public function hasClass($className) {
		foreach($this->stack(1) as $node) {
			if ( $this->is(".$className", $node))
				return true;
		}
		return false;
	}
	/**
	 * Enter description here...
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function removeClass($className) {
		foreach($this->stack(1) as $node) {
			$classes = explode( ' ', $node->getAttribute('class'));
			if ( in_array($className, $classes)) {
				$classes = array_diff($classes, array($className));
				if ( $classes )
					$node->setAttribute('class', implode(' ', $classes));
				else
					$node->removeAttribute('class');
			}
		}
		return $this;
	}
	/**
	 * Enter description here...
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function toggleClass($className) {
		foreach($this->stack(1) as $node) {
			if ( $this->is( $node, '.'.$className ))
				$this->removeClass($className);
			else
				$this->addClass($className);
		}
		return $this;
	}
	/**
	 * Proper name without underscore (just ->empty()) also works.
	 *
	 * Removes all child nodes from the set of matched elements.
	 *
	 * Example:
	 * querySelector("p")._empty()
	 *
	 * HTML:
	 * <p>Hello, <span>Person</span> <a href="#">and person</a></p>
	 *
	 * Result:
	 * [ <p></p> ]
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 * @access private
	 */
	public function _empty() {
		foreach($this->stack(1) as $node) {
			$node->nodeValue = '';
		}
		return $this;
	}
	/**
	 * Enter description here...
	 *
	 * @param array|string $callback Expects $node as first param, $index as second
	 * @param array $scope External variables passed to callback. Use compact('varName1', 'varName2'...) and extract($scope)
	 * @param array $arg1 Will ba passed as third and futher args to callback.
	 * @param array $arg2 Will ba passed as fourth and futher args to callback, and so on...
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function each($callback, $param1 = null, $param2 = null, $param3 = null) {
		$paramStructure = null;
		if (func_num_args() > 1) {
			$paramStructure = func_get_args();
			$paramStructure = array_slice($paramStructure, 1);
		}
		foreach($this->elements as $v)
			ArcaelaDOM::callbackRun($callback, array($v), $paramStructure);
		return $this;
	}
	/**
	 * Run callback on actual object.
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function callback($callback, $param1 = null, $param2 = null, $param3 = null) {
		$params = func_get_args();
		$params[0] = $this;
		ArcaelaDOM::callbackRun($callback, $params);
		return $this;
	}
	/**
	 * Enter description here...
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 * @todo add $scope and $args as in each() ???
	 */
	public function map($callback, $param1 = null, $param2 = null, $param3 = null) {
		$params = func_get_args();
		array_unshift($params, $this->elements);
		return $this->newInstance(
			call_user_func_array(array(ArcaelaDOM::class, 'map'), $params)
		);
	}
	/**
	 * Enter description here...
	 * 
	 * @param <type> $key
	 * @param <type> $value
	 */
	public function data($key, $value = null) {
		if (! isset($value)) {
			return ArcaelaDOM::data($this->get(0), $key, $value, $this->getDocumentID());
		} else {
			foreach($this as $node)
				ArcaelaDOM::data($node, $key, $value, $this->getDocumentID());
			return $this;
		}
	}
	/**
	 * Enter description here...
	 * 
	 * @param <type> $key
	 */
	public function removeData($key) {
		foreach($this as $node)
			ArcaelaDOM::removeData($node, $key, $this->getDocumentID());
		return $this;
	}
	/**
   * @access private
	 */
	public function rewind(){
		$this->debug('iterating foreach');
		$this->elementsBackup = $this->elements;
		$this->elementsInterator = $this->elements;
		$this->valid = isset( $this->elements[0] )
			? 1 : 0;
		$this->current = 0;
	}
	/**
   * @access private
	 */
	public function current(){
		return $this->elementsInterator[ $this->current ];
	}
	/**
   * @access private
	 */
	public function key(){
		return $this->current;
	}
	/**
	 * Double-function method.
	 *
	 * First: main iterator interface method.
	 * Second: Returning next sibling, alias for _next().
	 *
	 * Proper functionality is choosed automagicaly.
	 *
	 * @see ArcaelaElement::_next()
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public function next($cssSelector = null){
		$this->valid = isset( $this->elementsInterator[ $this->current+1 ] )
			? true
			: false;
		if (! $this->valid && $this->elementsInterator) {
			$this->elementsInterator = null;
		} else if ($this->valid) {
			$this->current++;
		} else {
			return $this->_next($cssSelector);
		}
	}
	/**
   * @access private
	 */
	public function valid(){
		return $this->valid;
	}
	/**
   * @access private
	 */
	public function offsetExists($offset) {
		return $this->find($offset)->size() > 0;
	}
	/**
   * @access private
	 */
	public function offsetGet($offset) {
		return $this->find($offset);
	}
	/**
   * @access private
	 */
	public function offsetSet($offset, $value) {
		$this->find($offset)->html($value);
	}
	/**
   * @access private
	 */
	public function offsetUnset($offset) {
		throw new \Exception("Can't do unset, use array interface only for calling queries and replacing HTML.");
	}
	/**
	 * Returns node's XPath.
	 *
	 * @param unknown_type $oneNode
	 * @return string
	 * @TODO use native getNodePath is avaible
	 * @access private
	 */
	protected function getNodeXpath($oneNode = null, $namespace = null) {
		$return = array();
		$loop = $oneNode
			? array($oneNode)
			: $this->elements;
		foreach($loop as $node) {
			if ($node instanceof \DOMDOCUMENT) {
				$return[] = '';
				continue;
			}
			$xpath = array();
			while(! ($node instanceof \DOMDOCUMENT)) {
				$i = 1;
				$sibling = $node;
				while($sibling->previousSibling) {
					$sibling = $sibling->previousSibling;
					$isElement = $sibling instanceof \DOMELEMENT;
					if ($isElement && $sibling->tagName == $node->tagName)
						$i++;
				}
				$xpath[] = $this->isXML()
					? "*[local-name()='{$node->tagName}'][{$i}]"
					: "{$node->tagName}[{$i}]";
				$node = $node->parentNode;
			}
			$xpath = join('/', array_reverse($xpath));
			$return[] = '/'.$xpath;
		}
		return $oneNode
			? $return[0]
			: $return;
	}
	public function whois($oneNode = null) {
		$return = array();
		$loop = $oneNode
			? array( $oneNode )
			: $this->elements;
		foreach($loop as $node) {
			if (isset($node->tagName)) {
				$tag = in_array($node->tagName, array('php', 'js'))
					? strtoupper($node->tagName)
					: $node->tagName;
				$return[] = $tag
					.($node->getAttribute('id')
						? '#'.$node->getAttribute('id'):'')
					.($node->getAttribute('class')
						? '.'.join('.', explode(' ', $node->getAttribute('class'))):'')
					.($node->getAttribute('name')
						? '[name="'.$node->getAttribute('name').'"]':'')
					.($node->getAttribute('value') && strpos($node->getAttribute('value'), '<'.'?php') === false
						? '[value="'.substr(str_replace("\n", '', $node->getAttribute('value')), 0, 15).'"]':'')
					.($node->getAttribute('value') && strpos($node->getAttribute('value'), '<'.'?php') !== false
						? '[value=PHP]':'')
					.($node->getAttribute('selected')
						? '[selected]':'')
					.($node->getAttribute('checked')
						? '[checked]':'')
				;
			} else if ($node instanceof \DOMTEXT) {
				if (trim($node->textContent))
					$return[] = 'Text:'.substr(str_replace("\n", ' ', $node->textContent), 0, 15);
			} else {
			}
		}
		return $oneNode && isset($return[0])
			? $return[0]
			: $return;
	}
	/**
	 * Dump htmlOuter and preserve chain. Usefull for debugging.
	 *
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 *
	 */
	public function dump() {
		print 'DUMP #'.(ArcaelaDOM::$dumpCount++).' ';
		$debug = ArcaelaDOM::$debug;
		ArcaelaDOM::$debug = false;
		var_dump($this->htmlOuter());
		return $this;
	}
	public function dumpWhois() {
		print 'DUMP #'.(ArcaelaDOM::$dumpCount++).' ';
		$debug = ArcaelaDOM::$debug;
		ArcaelaDOM::$debug = false;
		var_dump('whois', $this->whois());
		ArcaelaDOM::$debug = $debug;
		return $this;
	}
	public function dumpLength() {
		print 'DUMP #'.(ArcaelaDOM::$dumpCount++).' ';
		$debug = ArcaelaDOM::$debug;
		ArcaelaDOM::$debug = false;
		var_dump('length', $this->length );
		ArcaelaDOM::$debug = $debug;
		return $this;
	}
	public function dumpTree($html = true, $title = true) {
		$output = $title
			? 'DUMP #'.(ArcaelaDOM::$dumpCount++)." \n" : '';
		$debug = ArcaelaDOM::$debug;
		ArcaelaDOM::$debug = false;
		foreach($this->stack() as $node)
			$output .= $this->__dumpTree($node);
		ArcaelaDOM::$debug = $debug;
		print $html
			? nl2br(str_replace(' ', '&nbsp;', $output))
			: $output;
		return $this;
	}
	private function __dumpTree($node, $intend = 0) {
		$whois = $this->whois($node);
		$return = '';
		if ($whois)
			$return .= str_repeat(' - ', $intend).$whois."\n";
		if (isset($node->childNodes))
			foreach($node->childNodes as $chNode)
				$return .= $this->__dumpTree($chNode, $intend+1);
		return $return;
	}
	/**
	 * Dump htmlOuter and stop script execution. Usefull for debugging.
	 *
	 */
	public function dumpDie() {
		print __FILE__.':'.__LINE__;
		var_dump($this->htmlOuter());
		die();
	}
}
/**
 *  mb_internal_encoding()
 *
 *  Included for mbstring pseudo-compatability.
 */
if (!function_exists('mb_internal_encoding')){
	function mb_internal_encoding($enc) {return true; }
}
/**
 *  mb_regex_encoding()
 *
 *  Included for mbstring pseudo-compatability.
 */
if (!function_exists('mb_regex_encoding')){
	function mb_regex_encoding($enc) {return true; }
}
/**
 *  mb_strlen()
 *
 *  Included for mbstring pseudo-compatability.
 */
if (!function_exists('mb_strlen')){
	function mb_strlen($str)
	{
		return strlen($str);
	}
}
/**
 *  mb_strpos()
 *
 *  Included for mbstring pseudo-compatability.
 */
if (!function_exists('mb_strpos')){
	function mb_strpos($haystack, $needle, $offset=0)
	{
		return strpos($haystack, $needle, $offset);
	}
}
/**
 *  mb_stripos()
 *
 *  Included for mbstring pseudo-compatability.
 */
if (!function_exists('mb_stripos')){
	function mb_stripos($haystack, $needle, $offset=0)
	{
		return stripos($haystack, $needle, $offset);
	}
}
/**
 *  mb_substr()
 *
 *  Included for mbstring pseudo-compatability.
 */
if (!function_exists('mb_substr')){
	function mb_substr($str, $start, $length=0)
	{
		return substr($str, $start, $length);
	}
}
/**
 *  mb_substr_count()
 *
 *  Included for mbstring pseudo-compatability.
 */
if (!function_exists('mb_substr_count')){
	function mb_substr_count($haystack, $needle)
	{
		return substr_count($haystack, $needle);
	}
}
/**
 * Static namespace for ArcaelaDOM functions.
 *
 * @package ArcaelaDOM
 */
abstract class ArcaelaDOM {
	/**
	 * XXX: Workaround for mbstring problems 
	 * 
	 * @var bool
	 */
	public static $mbstringSupport = true;
	public static $debug = false;
	public static $documents = array();
	public static $defaultDocumentID = null;
	/**
	 * Applies only to HTML.
	 *
	 * @var unknown_type
	 */
	public static $defaultDoctype = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
	public static $defaultCharset = 'UTF-8';
	/**
	 * Static namespace for plugins.
	 *
	 * @var object
	 */
	public static $plugins = array();
	/**
	 * List of loaded plugins.
	 *
	 * @var unknown_type
	 */
	public static $pluginsLoaded = array();
	public static $pluginsMethods = array();
	public static $pluginsStaticMethods = array();
	public static $extendMethods = array();
	/**
	 * @TODO implement
	 */
	public static $extendStaticMethods = array();
	/**
	 * Hosts allowed for AJAX connections.
	 * Dot '.' means $_SERVER['HTTP_HOST'] (if any).
	 *
	 * @var array
	 */
	public static $ajaxAllowedHosts = array(
		'.'
	);
	/**
	 * AJAX settings.
	 *
	 * @var array
	 * XXX should it be static or not ?
	 */
	public static $ajaxSettings = array(
		'url' => '',//TODO
		'global' => true,
		'type' => "GET",
		'timeout' => null,
		'contentType' => "application/x-www-form-urlencoded",
		'processData' => true,
		'data' => null,
		'username' => null,
		'password' => null,
		'accepts' => array(
			'xml' => "application/xml, text/xml",
			'html' => "text/html",
			'script' => "text/javascript, application/javascript",
			'json' => "application/json, text/javascript",
			'text' => "text/plain",
			'_default' => "*/*"
		)
	);
	public static $lastModified = null;
	public static $active = 0;
	public static $dumpCount = 0;
	public static function querySelector($arg1, $context = null) {
		if ($arg1 instanceof \DOMNODE && ! isset($context)) {
			foreach(ArcaelaDOM::$documents as $documentWrapper) {
				$compare = $arg1 instanceof \DOMDocument
					? $arg1 : $arg1->ownerDocument;
				if ($documentWrapper->document->isSameNode($compare))
					$context = $documentWrapper->id;
			}
		}
		if (! $context) {
			$domId = self::$defaultDocumentID;
			if (! $domId)
				throw new \Exception("Can't use last created DOM, because there isn't any. Use ArcaelaDOM::newDocument() first.");
		} else if (is_object($context) && $context instanceof ArcaelaElement)
			$domId = $context->getDocumentID();
		else if ($context instanceof \DOMDOCUMENT) {
			$domId = self::getDocumentID($context);
			if (! $domId) {
				$domId = self::newDocument($context)->getDocumentID();
			}
		} else if ($context instanceof \DOMNODE) {
			$domId = self::getDocumentID($context);
			if (! $domId) {
				throw new \Exception('Orphaned \DOMNode');
			}
		} else
			$domId = $context;
		if ($arg1 instanceof ArcaelaElement) {
			if ($arg1->getDocumentID() == $domId)
				return $arg1;
			$class = get_class($arg1);
			$ArcaelaDOM = $class != ArcaelaDOM::class
				? new $class($arg1, $domId)
				: new ArcaelaElement($domId);
			$ArcaelaDOM->elements = array();
			foreach($arg1->elements as $node)
				$ArcaelaDOM->elements[] = $ArcaelaDOM->document->importNode($node, true);
			return $ArcaelaDOM;
		} else if ($arg1 instanceof \DOMNODE || (is_array($arg1) && isset($arg1[0]) && $arg1[0] instanceof \DOMNODE)) {
			$ArcaelaDOM = new ArcaelaElement($domId);
			if (!($arg1 instanceof \DOMNODELIST) && ! is_array($arg1))
				$arg1 = array($arg1);
			$ArcaelaDOM->elements = array();
			foreach($arg1 as $node) {
				$sameDocument = $node->ownerDocument instanceof \DOMDOCUMENT
					&& ! $node->ownerDocument->isSameNode($ArcaelaDOM->document);
				$ArcaelaDOM->elements[] = $sameDocument
					? $ArcaelaDOM->document->importNode($node, true)
					: $node;
			}
			return $ArcaelaDOM;
		} else if (self::isMarkup($arg1)) {
			$ArcaelaDOM = new ArcaelaElement($domId);
			return $ArcaelaDOM->newInstance(
				$ArcaelaDOM->documentWrapper->import($arg1)
			);
		} else {
			$ArcaelaDOM = new ArcaelaElement($domId);
			if ($context && $context instanceof ArcaelaElement)
				$ArcaelaDOM->elements = $context->elements;
			else if ($context && $context instanceof \DOMNODELIST) {
				$ArcaelaDOM->elements = array();
				foreach($context as $node)
					$ArcaelaDOM->elements[] = $node;
			} else if ($context && $context instanceof \DOMNODE)
				$ArcaelaDOM->elements = array($context);
			return $ArcaelaDOM->find($arg1);
		}
	}
	/**
	 * Sets default document to $id. Document has to be loaded prior
	 * to using this method.
	 * $id can be retrived via getDocumentID() or getDocumentIDRef().
	 *
	 * @param unknown_type $id
	 */
	public static function selectDocument($id) {
		$id = self::getDocumentID($id);
		self::debug("Selecting document '$id' as default one");
		self::$defaultDocumentID = self::getDocumentID($id);
	}
	/**
	 * Returns document with id $id or last used as ArcaelaElement.
	 * $id can be retrived via getDocumentID() or getDocumentIDRef().
	 * Chainable.
	 *
	 * @see ArcaelaDOM::selectDocument()
	 * @param unknown_type $id
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public static function getDocument($id = null) {
		if ($id)
			ArcaelaDOM::selectDocument($id);
		else
			$id = ArcaelaDOM::$defaultDocumentID;
		return new ArcaelaElement($id);
	}
	/**
	 * Creates new document from markup.
	 * Chainable.
	 *
	 * @param unknown_type $markup
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public static function newDocument($markup = null, $contentType = null) {
		if (! $markup)
			$markup = '';
		$documentID = ArcaelaDOM::createDocumentWrapper($markup, $contentType);
		return new ArcaelaElement($documentID);
	}
	/**
	 * Creates new document from markup.
	 * Chainable.
	 *
	 * @param unknown_type $markup
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public static function newDocumentHTML($markup = null, $charset = null) {
		$contentType = $charset
			? ";charset=$charset"
			: '';
		return self::newDocument($markup, "text/html{$contentType}");
	}
	/**
	 * Creates new document from markup.
	 * Chainable.
	 *
	 * @param unknown_type $markup
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public static function newDocumentXML($markup = null, $charset = null) {
		$contentType = $charset
			? ";charset=$charset"
			: '';
		return self::newDocument($markup, "text/xml{$contentType}");
	}
	/**
	 * Creates new document from markup.
	 * Chainable.
	 *
	 * @param unknown_type $markup
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public static function newDocumentXHTML($markup = null, $charset = null) {
		$contentType = $charset
			? ";charset=$charset"
			: '';
		return self::newDocument($markup, "application/xhtml+xml{$contentType}");
	}
	/**
	 * Creates new document from markup.
	 * Chainable.
	 *
	 * @param unknown_type $markup
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public static function newDocumentPHP($markup = null, $contentType = "text/html") {
		$markup = ArcaelaDOM::phpToMarkup($markup, self::$defaultCharset);
		return self::newDocument($markup, $contentType);
	}
	public static function phpToMarkup($php, $charset = 'utf-8') {
		$regexes = array(
			'@(<(?!\\?)(?:[^>]|\\?>)+\\w+\\s*=\\s*)(\')([^\']*)<'.'?php?(.*?)(?:\\?>)([^\']*)\'@s',
			'@(<(?!\\?)(?:[^>]|\\?>)+\\w+\\s*=\\s*)(")([^"]*)<'.'?php?(.*?)(?:\\?>)([^"]*)"@s',
		);
		foreach($regexes as $regex)
			while (preg_match($regex, $php, $matches)) {
				$php = preg_replace_callback(
					$regex,
					array(ArcaelaDOM::class, '_phpToMarkupCallback'),
					$php
				);
			}
		$regex = '@(^|>[^<]*)+?(<\?php(.*?)(\?>))@s';
		$php = preg_replace($regex, '\\1<php><!-- \\3 --></php>', $php);
		return $php;
	}
	public static function _phpToMarkupCallback($m, $charset = 'utf-8') {
		return $m[1].$m[2]
			.htmlspecialchars("<"."?php".$m[4]."?".">", ENT_QUOTES|ENT_NOQUOTES, $charset)
			.$m[5].$m[2];
	}
	public static function _markupToPHPCallback($m) {
		return "<"."?php ".htmlspecialchars_decode($m[1])." ?".">";
	}
	/**
	 * Converts document markup containing PHP code generated by ArcaelaDOM::php()
	 * into valid (executable) PHP code syntax.
	 *
	 * @param string|ArcaelaElement $content
	 * @return string PHP code.
	 */
	public static function markupToPHP($content) {
		if ($content instanceof ArcaelaElement)
			$content = $content->markupOuter();
		/* <php>...</php> to <?php...? > */
		$content = preg_replace_callback(
			'@<php>\s*<!--(.*?)-->\s*</php>@s',
			array(ArcaelaDOM::class, '_markupToPHPCallback'),
			$content
		);
		/* <node attr='< ?php ? >'> extra space added to save highlighters */
		$regexes = array(
			'@(<(?!\\?)(?:[^>]|\\?>)+\\w+\\s*=\\s*)(\')([^\']*)(?:&lt;|%3C)\\?(?:php)?(.*?)(?:\\?(?:&gt;|%3E))([^\']*)\'@s',
			'@(<(?!\\?)(?:[^>]|\\?>)+\\w+\\s*=\\s*)(")([^"]*)(?:&lt;|%3C)\\?(?:php)?(.*?)(?:\\?(?:&gt;|%3E))([^"]*)"@s',
		);
		foreach($regexes as $regex)
			while (preg_match($regex, $content))
				$content = preg_replace_callback(
					$regex,
					create_function('$m',
						'return $m[1].$m[2].$m[3]."<?php "
							.str_replace(
								array("%20", "%3E", "%09", "&#10;", "&#9;", "%7B", "%24", "%7D", "%22", "%5B", "%5D"),
								array(" ", ">", "	", "\n", "	", "{", "$", "}", \'"\', "[", "]"),
								htmlspecialchars_decode($m[4])
							)
							." ?>".$m[5].$m[2];'
					),
					$content
				);
		return $content;
	}
	/**
	 * Creates new document from file $file.
	 * Chainable.
	 *
	 * @param string $file URLs allowed. See File wrapper page at php.net for more supported sources.
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public static function newDocumentFile($file, $contentType = null) {
		$documentID = self::createDocumentWrapper(
			file_get_contents($file), $contentType
		);
		return new ArcaelaElement($documentID);
	}
	/**
	 * Creates new document from markup.
	 * Chainable.
	 *
	 * @param unknown_type $markup
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public static function newDocumentFileHTML($file, $charset = null) {
		$contentType = $charset
			? ";charset=$charset"
			: '';
		return self::newDocumentFile($file, "text/html{$contentType}");
	}
	/**
	 * Creates new document from markup.
	 * Chainable.
	 *
	 * @param unknown_type $markup
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public static function newDocumentFileXML($file, $charset = null) {
		$contentType = $charset
			? ";charset=$charset"
			: '';
		return self::newDocumentFile($file, "text/xml{$contentType}");
	}
	/**
	 * Creates new document from markup.
	 * Chainable.
	 *
	 * @param unknown_type $markup
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public static function newDocumentFileXHTML($file, $charset = null) {
		$contentType = $charset
			? ";charset=$charset"
			: '';
		return self::newDocumentFile($file, "application/xhtml+xml{$contentType}");
	}
	/**
	 * Creates new document from markup.
	 * Chainable.
	 *
	 * @param unknown_type $markup
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 */
	public static function newDocumentFilePHP($file, $contentType = null) {
		return self::newDocumentPHP(file_get_contents($file), $contentType);
	}
	/**
	 * Reuses existing \DOMDocument object.
	 * Chainable.
	 *
	 * @param $document \DOMDocument
	 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
	 * @TODO support \DOMDocument
	 */
	public static function loadDocument($document) {
		die('TODO loadDocument');
	}
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $html
	 * @param unknown_type $domId
	 * @return unknown New DOM ID
	 * @todo support PHP tags in input
	 * @todo support passing \DOMDocument object from self::loadDocument
	 */
	protected static function createDocumentWrapper($html, $contentType = null, $documentID = null) {
		if (function_exists('domxml_open_mem'))
			throw new \Exception("Old PHP4 DOM XML extension detected. ArcaelaDOM won't work until this extension is enabled.");
		$document = null;
		if ($html instanceof \DOMDOCUMENT) {
			if (self::getDocumentID($html)) {
				$document = clone $html;
			} else {
				$wrapper = new DOMDocumentWrapper($html, $contentType, $documentID);
			}
		} else {
			$wrapper = new DOMDocumentWrapper($html, $contentType, $documentID);
		}
		ArcaelaDOM::$documents[$wrapper->id] = $wrapper;
		ArcaelaDOM::selectDocument($wrapper->id);
		return $wrapper->id;
	}
	/**
	 * Extend class namespace.
	 *
	 * @param string|array $target
	 * @param array $source
	 * @TODO support string $source
	 * @return unknown_type
	 */
	public static function extend($target, $source) {
		switch($target) {
			case 'ArcaelaElement':
				$targetRef = &self::$extendMethods;
				$targetRef2 = &self::$pluginsMethods;
				break;
			case ArcaelaDOM::class:
				$targetRef = &self::$extendStaticMethods;
				$targetRef2 = &self::$pluginsStaticMethods;
				break;
			default:
				throw new \Exception("Unsupported \$target type");
		}
		if (is_string($source))
			$source = array($source => $source);
		foreach($source as $method => $callback) {
			if (isset($targetRef[$method])) {
				self::debug("Duplicate method '{$method}', can\'t extend '{$target}'");
				continue;
			}
			if (isset($targetRef2[$method])) {
				self::debug("Duplicate method '{$method}' from plugin '{$targetRef2[$method]}',"
					." can\'t extend '{$target}'");
				continue;
			}
			$targetRef[$method] = $callback;
		}
		return true;
	}
	/**
	 * Extend ArcaelaDOM with $class from $file.
	 *
	 * @param string $class Extending class name. Real class name can be prepended ArcaelaDOM_.
	 * @param string $file Filename to include. Defaults to "{$class}.php".
	 */
	public static function plugin($class, $file = null) {
		if (in_array($class, (array) self::$pluginsLoaded))
			return true;
		if (! $file)
			$file = $class.'.php';
		$objectClassExists = class_exists('ArcaelaElementPlugin_'.$class);
		$staticClassExists = class_exists('ArcaelaDOMPlugin_'.$class);
		if (! $objectClassExists && ! $staticClassExists)
			require_once($file);
		self::$pluginsLoaded[] = $class;
		if (class_exists('ArcaelaDOMPlugin_'.$class)) {
			$realClass = 'ArcaelaDOMPlugin_'.$class;
			$vars = get_class_vars($realClass);
			$loop = isset($vars['ArcaelaDOMMethods'])
				&& ! is_null($vars['ArcaelaDOMMethods'])
				? $vars['ArcaelaDOMMethods']
				: get_class_methods($realClass);
			foreach($loop as $method) {
				if ($method == '__initialize')
					continue;
				if (! is_callable(array($realClass, $method)))
					continue;
				if (isset(self::$pluginsStaticMethods[$method])) {
					throw new \Exception("Duplicate method '{$method}' from plugin '{$c}' conflicts with same method from plugin '".self::$pluginsStaticMethods[$method]."'");
					return;
				}
				self::$pluginsStaticMethods[$method] = $class;
			}
			if (method_exists($realClass, '__initialize'))
				call_user_func_array(array($realClass, '__initialize'), array());
		}
		if (class_exists('ArcaelaElementPlugin_'.$class)) {
			$realClass = 'ArcaelaElementPlugin_'.$class;
			$vars = get_class_vars($realClass);
			$loop = isset($vars['ArcaelaDOMMethods'])
				&& ! is_null($vars['ArcaelaDOMMethods'])
				? $vars['ArcaelaDOMMethods']
				: get_class_methods($realClass);
			foreach($loop as $method) {
				if (! is_callable(array($realClass, $method)))
					continue;
				if (isset(self::$pluginsMethods[$method])) {
					throw new \Exception("Duplicate method '{$method}' from plugin '{$c}' conflicts with same method from plugin '".self::$pluginsMethods[$method]."'");
					continue;
				}
				self::$pluginsMethods[$method] = $class;
			}
		}
		return true;
	}
	/**
	 * Unloades all or specified document from memory.
	 *
	 * @param mixed $documentID @see ArcaelaDOM::getDocumentID() for supported types.
	 */
	public static function unloadDocuments($id = null) {
		if (isset($id)) {
			if ($id = self::getDocumentID($id))
				unset(ArcaelaDOM::$documents[$id]);
		} else {
			foreach(ArcaelaDOM::$documents as $k => $v) {
				unset(ArcaelaDOM::$documents[$k]);
			}
		}
	}
	/**
	 * Parses ArcaelaDOM object or HTML result against PHP tags and makes them active.
	 *
	 * @param ArcaelaDOM|string $content
	 * @deprecated
	 * @return string
	 */
	public static function unsafePHPTags($content) {
		return self::markupToPHP($content);
	}
	public static function DOMNodeListToArray($DOMNodeList) {
		$array = array();
		if (! $DOMNodeList)
			return $array;
		foreach($DOMNodeList as $node)
			$array[] = $node;
		return $array;
	}
	/**
	 * Checks if $input is HTML string, which has to start with '<'.
	 *
	 * @param String $input
	 * @return Bool
	 * @todo still used ?
	 */
	public static function isMarkup($input) {
		return ! is_array($input) && substr(trim($input), 0, 1) == '<';
	}
	public static function debug($text) {
		if (self::$debug)
			print var_dump($text);
	}
	static function httpData($data, $type, $options) {
		if (isset($options['dataFilter']) && $options['dataFilter'])
			$data = self::callbackRun($options['dataFilter'], array($data, $type));
		if (is_string($data)) {
			if ($type == "json") {
				if (isset($options['_jsonp']) && $options['_jsonp']) {
					$data = preg_replace('/^\s*\w+\((.*)\)\s*$/s', '$1', $data);
				}
				$data = self::parseJSON($data);
			}
		}
		return $data;
	}
	/**
	 * Enter description here...
	 *
	 * @param array|ArcaelaDOM $data
	 *
	 */
	public static function param($data) {
		return http_build_query($data, "", '&');
	}
	public static function get($url, $data = null, $callback = null, $type = null) {
		if (!is_array($data)) {
			$callback = $data;
			$data = null;
		}
		return ArcaelaDOM::ajax(array(
			'type' => 'GET',
			'url' => $url,
			'data' => $data,
			'success' => $callback,
			'dataType' => $type,
		));
	}
	public static function post($url, $data = null, $callback = null, $type = null) {
		if (!is_array($data)) {
			$callback = $data;
			$data = null;
		}
		return ArcaelaDOM::ajax(array(
			'type' => 'POST',
			'url' => $url,
			'data' => $data,
			'success' => $callback,
			'dataType' => $type,
		));
	}
	public static function getJSON($url, $data = null, $callback = null) {
		if (!is_array($data)) {
			$callback = $data;
			$data = null;
		}
		return ArcaelaDOM::ajax(array(
			'type' => 'GET',
			'url' => $url,
			'data' => $data,
			'success' => $callback,
			'dataType' => 'json',
		));
	}
	public static function ajaxSetup($options) {
		self::$ajaxSettings = array_merge(
			self::$ajaxSettings,
			$options
		);
	}
	public static function ajaxAllowHost($host1, $host2 = null, $host3 = null) {
		$loop = is_array($host1)
			? $host1
			: func_get_args();
		foreach($loop as $host) {
			if ($host && ! in_array($host, ArcaelaDOM::$ajaxAllowedHosts)) {
				ArcaelaDOM::$ajaxAllowedHosts[] = $host;
			}
		}
	}
	public static function ajaxAllowURL($url1, $url2 = null, $url3 = null) {
		$loop = is_array($url1)
			? $url1
			: func_get_args();
		foreach($loop as $url)
			ArcaelaDOM::ajaxAllowHost(parse_url($url, PHP_URL_HOST));
	}
	/**
	 * Returns source's document ID.
	 *
	 * @param $source \DOMNode|ArcaelaElement
	 * @return string
	 */
	public static function getDocumentID($source) {
		if ($source instanceof \DOMDOCUMENT) {
			foreach(ArcaelaDOM::$documents as $id => $document) {
				if ($source->isSameNode($document->document))
					return $id;
			}
		} else if ($source instanceof \DOMNODE) {
			foreach(ArcaelaDOM::$documents as $id => $document) {
				if ($source->ownerDocument->isSameNode($document->document))
					return $id;
			}
		} else if ($source instanceof ArcaelaElement)
			return $source->getDocumentID();
		else if (is_string($source) && isset(ArcaelaDOM::$documents[$source]))
			return $source;
	}
	/**
	 * Get \DOMDocument object related to $source.
	 * Returns null if such document doesn't exist.
	 *
	 * @param $source \DOMNode|ArcaelaElement|string
	 * @return string
	 */
	public static function getDOMDocument($source) {
		if ($source instanceof \DOMDOCUMENT)
			return $source;
		$source = self::getDocumentID($source);
		return $source
			? self::$documents[$source]['document']
			: null;
	}
	/**
	 *
	 * @return unknown_type
	 * @link http://docs.jquery.com/Utilities/jQuery.makeArray
	 */
	public static function makeArray($object) {
		$array = array();
		if (is_object($object) && $object instanceof \DOMNODELIST) {
			foreach($object as $value)
				$array[] = $value;
		} else if (is_object($object) && ! ($object instanceof \Iterator)) {
			foreach(get_object_vars($object) as $name => $value)
				$array[0][$name] = $value;
		} else {
			foreach($object as $name => $value)
				$array[0][$name] = $value;
		}
		return $array;
	}
	public static function inArray($value, $array) {
		return in_array($value, $array);
	}
	/**
	 *
	 * @param $object
	 * @param $callback
	 * @return unknown_type
	 * @link http://docs.jquery.com/Utilities/jQuery.each
	 */
	public static function each($object, $callback, $param1 = null, $param2 = null, $param3 = null) {
		$paramStructure = null;
		if (func_num_args() > 2) {
			$paramStructure = func_get_args();
			$paramStructure = array_slice($paramStructure, 2);
		}
		if (is_object($object) && ! ($object instanceof \Iterator)) {
			foreach(get_object_vars($object) as $name => $value)
				ArcaelaDOM::callbackRun($callback, array($name, $value), $paramStructure);
		} else {
			foreach($object as $name => $value)
				ArcaelaDOM::callbackRun($callback, array($name, $value), $paramStructure);
		}
	}
	/**
	 *
	 * @link http://docs.jquery.com/Utilities/jQuery.map
	 */
	public static function map($array, $callback, $param1 = null, $param2 = null, $param3 = null) {
		$result = array();
		$paramStructure = null;
		if (func_num_args() > 2) {
			$paramStructure = func_get_args();
			$paramStructure = array_slice($paramStructure, 2);
		}
		foreach($array as $v) {
			$vv = ArcaelaDOM::callbackRun($callback, array($v), $paramStructure);
			if (is_array($vv))  {
				foreach($vv as $vvv)
					$result[] = $vvv;
			} else if ($vv !== null) {
				$result[] = $vv;
			}
		}
		return $result;
	}
	/**
	 *
	 * @param $callback Callback
	 * @param $params
	 * @param $paramStructure
	 * @return unknown_type
	 */
	public static function callbackRun($callback, $params = array(), $paramStructure = null) {
		if (! $callback)
			return;
		if ($callback instanceof CallbackParameterToReference) {
			if (isset($params[0]))
				$callback->callback = $params[0];
			return true;
		}
		if ($callback instanceof Callback) {
			$paramStructure = $callback->params;
			$callback = $callback->callback;
		}
		if (! $paramStructure)
			return call_user_func_array($callback, $params);
		$p = 0;
		foreach($paramStructure as $i => $v) {
			$paramStructure[$i] = $v instanceof CallbackParam
				? $params[$p++]
				: $v;
		}
		return call_user_func_array($callback, $paramStructure);
	}
	/**
	 * Merge 2 ArcaelaDOM objects.
	 * @param array $one
	 * @param array $two
	 * @protected
	 * @todo node lists, ArcaelaElement
	 */
	public static function merge($one, $two) {
        $one = (object)$one;
        $two = (object)$two;
        $elements = $one->elements;
        foreach($two->elements as $node) {
			$exists = false;
			foreach($elements as $node2) {
				if ($node2->isSameNode($node))
					$exists = true;
			}
			if (! $exists)
				$elements[] = $node;
		}
		return $elements;
	}
	/**
	 *
	 * @param $array
	 * @param $callback
	 * @param $invert
	 * @return unknown_type
	 * @link http://docs.jquery.com/Utilities/jQuery.grep
	 */
	public static function grep($array, $callback, $invert = false) {
		$result = array();
		foreach($array as $k => $v) {
			$r = call_user_func_array($callback, array($v, $k));
			if ($r === !(bool)$invert)
				$result[] = $v;
		}
		return $result;
	}
	public static function unique($array) {
		return array_unique($array);
	}
	/**
	 *
	 * @param $function
	 * @return unknown_type
	 * @TODO there are problems with non-static methods, second parameter pass it
	 * 	but doesnt verify is method is really callable
	 */
	public static function isFunction($function) {
		return is_callable($function);
	}
	public static function trim($str) {
		return trim($str);
	}
	/* PLUGINS NAMESPACE */
	/**
	 *
	 * @param $url
	 * @param $callback
	 * @param $param1
	 * @param $param2
	 * @param $param3
	 * @return ArcaelaElement
	 */
	public static function browserGet($url, $callback, $param1 = null, $param2 = null, $param3 = null) {
		if (self::plugin('WebBrowser')) {
			$params = func_get_args();
			return self::callbackRun(array(self::$plugins, 'browserGet'), $params);
		} else {
			self::debug('WebBrowser plugin not available...');
		}
	}
	/**
	 *
	 * @param $url
	 * @param $data
	 * @param $callback
	 * @param $param1
	 * @param $param2
	 * @param $param3
	 * @return ArcaelaElement
	 */
	public static function browserPost($url, $data, $callback, $param1 = null, $param2 = null, $param3 = null) {
		if (self::plugin('WebBrowser')) {
			$params = func_get_args();
			return self::callbackRun(array(self::$plugins, 'browserPost'), $params);
		} else {
			self::debug('WebBrowser plugin not available...');
		}
	}
	/**
	 *
	 * @param $ajaxSettings
	 * @param $callback
	 * @param $param1
	 * @param $param2
	 * @param $param3
	 * @return ArcaelaElement
	 */
	public static function browser($ajaxSettings, $callback, $param1 = null, $param2 = null, $param3 = null) {
		if (self::plugin('WebBrowser')) {
			$params = func_get_args();
			return self::callbackRun(array(self::$plugins, 'browser'), $params);
		} else {
			self::debug('WebBrowser plugin not available...');
		}
	}
	/**
	 *
	 * @param $code
	 * @return string
	 */
	public static function php($code) {
		return self::code('php', $code);
	}
	/**
	 *
	 * @param $type
	 * @param $code
	 * @return string
	 */
	public static function code($type, $code) {
		return "<$type><!-- ".trim($code)." --></$type>";
	}
	public static function __callStatic($method, $params) {
		return call_user_func_array(
			array(ArcaelaDOM::$plugins, $method),
			$params
		);
	}
	protected static function dataSetupNode($node, $documentID) {
		foreach(ArcaelaDOM::$documents[$documentID]->dataNodes as $dataNode) {
			if ($node->isSameNode($dataNode))
				return $dataNode;
		}
		ArcaelaDOM::$documents[$documentID]->dataNodes[] = $node;
		return $node;
	}
	protected static function dataRemoveNode($node, $documentID) {
		foreach(ArcaelaDOM::$documents[$documentID]->dataNodes as $k => $dataNode) {
			if ($node->isSameNode($dataNode)) {
				unset(self::$documents[$documentID]->dataNodes[$k]);
				unset(self::$documents[$documentID]->data[ $dataNode->dataID ]);
			}
		}
	}
	public static function data($node, $name, $data, $documentID = null) {
		if (! $documentID)
			$documentID = self::getDocumentID($node);
		$document = ArcaelaDOM::$documents[$documentID];
		$node = self::dataSetupNode($node, $documentID);
		if (! isset($node->dataID))
			$node->dataID = ++ArcaelaDOM::$documents[$documentID]->uuid;
		$id = $node->dataID;
		if (! isset($document->data[$id]))
			$document->data[$id] = array();
		if (! is_null($data))
			$document->data[$id][$name] = $data;
		if ($name) {
			if (isset($document->data[$id][$name]))
				return $document->data[$id][$name];
		} else
			return $id;
	}
	public static function removeData($node, $name, $documentID) {
		if (! $documentID)
			$documentID = self::getDocumentID($node);
		$document = ArcaelaDOM::$documents[$documentID];
		$node = self::dataSetupNode($node, $documentID);
		$id = $node->dataID;
		if ($name) {
			if (isset($document->data[$id][$name]))
				unset($document->data[$id][$name]);
			$name = null;
			foreach($document->data[$id] as $name)
				break;
			if (! $name)
				self::removeData($node, $name, $documentID);
		} else {
			self::dataRemoveNode($node, $documentID);
		}
	}
}
/**
 * Plugins static namespace class.
 *
 * @package ArcaelaDOM
 * @todo move plugin methods here (as statics)
 */
class ArcaelaDOMPlugins {
	public function __call($method, $args) {
		if (isset(ArcaelaDOM::$extendStaticMethods[$method])) {
			$return = call_user_func_array(
				ArcaelaDOM::$extendStaticMethods[$method],
				$args
			);
		} else if (isset(ArcaelaDOM::$pluginsStaticMethods[$method])) {
			$class = ArcaelaDOM::$pluginsStaticMethods[$method];
			$realClass = "ArcaelaDOMPlugin_$class";
			$return = call_user_func_array(
				array($realClass, $method),
				$args
			);
			return isset($return)
				? $return
				: $this;
		} else
			throw new \Exception("Method '{$method}' doesnt exist");
	}
}
/**
 * Shortcut to ArcaelaDOM::querySelector($arg1, $context)
 * Chainable.
 *
 * @see ArcaelaDOM::querySelector()
 * @return ArcaelaElement|QueryTemplatesSource|QueryTemplatesParse|QueryTemplatesSourceQuery
 * @package ArcaelaDOM
 */
function querySelector($arg1, $context = null) {
	$args = func_get_args();
	return call_user_func_array(
		array(ArcaelaDOM::class, 'querySelector'),
		$args
	);
}
ArcaelaDOM::$plugins = new ArcaelaDOMPlugins();