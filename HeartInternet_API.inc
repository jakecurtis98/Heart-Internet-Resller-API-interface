<?php
  /**
   * This provides easy access to the Heart Internet API. Please see
   * {@link http://api.heartinternet.uk/api2.html} for the API documentation
   * itself.
   * 
   * External methods:
   *
   * $hi_api->connect(bool); // true = connect to the test API, false = connect to the live API. Default is false.
   * $hi_api->logIn($username, $password, $objects, $extensions);
   * $hi_api->sendMessage($xml_to_send, bool); // true = return array, false = return XML. Default is true. 
   *
   * Usage Example:
   *
   * <code>
   * <?php
   * require_once('../HeartInternet_API.inc');
   * $hi_api = new HeartInternet_API();
   * $hi_api->connect(true); // true = connect to the test API, false = connect to the live API.  
   * $username = "your-username";
   * $password = "your-password";
   * $hi_api->logIn($username, $password, $objects, $extensions);
   * $some_xml = <<<XML
   * <?xml version="1.0"?>
   * <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
   * <command>
   * (your command)
   * </command>
   * </epp>
   * XML;
   * $returned_xml = $hi_api->sendMessage($some_xml, true);
   * echo "XML Sent: <br />" . htmlentities($some_xml) . "<br />";
   * echo "XML Received: <br /> " . htmlentities($returned_xml) . "<br />";
   * ?>
   * </code>
   *
   *
   * 
   * Recent changes:
   * 0.2: Improved fread() usage for cases where there are over 8192/16384 bytes
   *      to read.
   * 0.3: Documentation updates - example usage, methods. 
   * 0.4: Added local validation to logIn() to cover common error scenarios.
   *
   * @copyright Copyright 2011, Heart Internet Ltd
   * @version 0.5
   */
  class HeartInternet_API {
    /**
     * @var string The main API namespace
     */
    public $namespace = "urn:ietf:params:xml:ns:epp-1.0";
    private $hostname = "api.heartinternet.uk";
    /**
     * Connects to the API server and returns the greeting (as literal XML).
     *
     * @param boolean $test_mode Set to true if you want to connect to the test service instead.
     */
    public function connect($test_mode=false) {
      $this->res = fsockopen("tls://" . $this->hostname, $test_mode ? 1701 : 700);
      return $this->getResponse();
    }
    function getResponse() {
      $size_packed = fread($this->res, 4);
      if(strlen($size_packed) == 0) return;
      $size = unpack("N", $size_packed);
      // This works around PHP's fread() capping at 8192 bytes
      $out = "";
      $last = "";
      for($s = $size[1]-4; $s>0; $s-=strlen($last)) {
        $last = fread($this->res, $s);
        $out.=$last;
      }
      return $out;
    }
    /**
     * This sends an XML message to the API, and returns the result, as an
     * array by default. This will throw an exception in the case of an internal failure.
     *
     * @param string $output The XML message to send
     * @param boolean $no_parsing Set to true if you want the raw XML response returned.
     */
    public function sendMessage($output, $no_parsing=false) {
      fwrite($this->res, pack("N", strlen($output)+4) . $output);
      $content = $this->getResponse();

      if($content) {
        if($no_parsing) {
          return $content;
        } else {
          $result=array();
          $parser = xml_parser_create();
          xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
          xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
          xml_parse_into_struct($parser, $content, $result);
          // Do something with $result
          return $result;
        }
      } else {
        // Error handling
        throw new Exception("Communication failure");
        return;
      }
    }
    /**
     * Logs you in (once connected). Will raise an exception on failure.
     * @param string $userid Your API user ID, see the API documentation.
     * @param string $password Your API password.
     * @param array $objects The object namespaces to load. Required.
     * @param array $extensions the extension namespaces to load.
     */
    public function logIn($userid, $password, $objects, $extensions) {
      if(!preg_match('/^[a-f0-9]+$/', $userid)) {
        throw new Exception(
          "Invalid username, should look like '9cf2cdbcce5e00c0'"
        );
      }

      if( (!$objects) || empty($objects) ) {
        throw new Exception(
          "You must provide some object namespaces, please see the login examples in the documentation"
        );
      }

      $doc = new DOMDocument();
      $content          = $doc->createElement("login");
      $clID_element = $doc->createElement('clID');
      $clID_element->appendChild(
        $doc->createTextNode($userid)
      );
      $content->appendChild($clID_element);

      $pw_element = $doc->createElement('pw');
      $pw_element->appendChild(
        $doc->createTextNode($password)
      );
      $content->appendChild($pw_element);

      $options_element = $doc->createElement('options');

      $version_element = $doc->createElement('version');
      $version_element->appendChild( $doc->createTextNode("1.0"));
      $options_element->appendChild($version_element);

      $lang_element = $doc->createElement('lang');
      $lang_element->appendChild( $doc->createTextNode("en"));
      $options_element->appendChild($lang_element);

      $content->appendChild($options_element);

      $svcs_element = $doc->createElement('svcs');

      foreach($objects as $object) {
        $element = $doc->createElement('objURI');
        $element->appendChild($doc->createTextNode("$object"));
        $svcs_element->appendChild($element);
      }
   
      $svcs_extensions = $doc->createElement('svcExtension');
      foreach($extensions as $extension) {
        $element = $doc->createElement('extURI');
        $element->appendChild($doc->createTextNode("$extension"));
        $svcs_extensions->appendChild($element);
      }
      $svcs_element->appendChild($svcs_extensions);

      $content->appendChild($svcs_element);

      $xml = $this->buildXML($content);
      $result = $this->sendMessage($xml);
      // error_log($xml);
      foreach($result as $tag) {
        if(
          $tag['tag']=="result" && 
          $tag['type']!="close" && 
          $tag['attributes']['code']!=1000
        ) throw new Exception("Failed to log in!: " . $tag['attributes']['code']);

        if($tag['tag']=="session-id") return $tag['value'];
      }
      return $result;
    }
    /**
     * This transforms a DOMDocument for the inner part of the request (inside
     * <command/>) into an XML string.
     *
     * @param DOMDocument $content
     */
    public function buildXML($content) {
      $doc = $content->ownerDocument;
      $epp = $doc->createElement("epp");
      $epp->setAttribute("xmlns", $this->namespace);
      $doc->appendChild($epp);
    
      $c = $doc->createElement('command');
      $epp->appendChild($c);
      $c->appendChild($content);
    
      $output = $doc->saveXML();
      return $output;
    }
    
    /**
     * Disconnects from the API server.
     */
    public function disconnect() {
      fclose($this->res);
    }
  }
?>
