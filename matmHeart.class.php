<?php
	require_once 'HeartInternet_API.inc';

	class matmHeart {

		private $username;
		private $password;
		/**
		 * @var $hi_api HeartInternet_API
		 */
		private $hi_api;

		public function __construct($username, $password) {
			$this->username = $username;
			$this->password = $password;
		}

		public function login( $objects, $extensions, $test=false ) {
			$this->hi_api = new HeartInternet_API();
			$this->hi_api->connect($test); // true = connect to the test API, false = connect to the live API.
			try {
				$this->hi_api->logIn( $this->username, $this->password, $objects, $extensions );
			} catch ( Exception $e ) {
				print "Error: " . $e->getMessage();
			}
		}

		public function getPackages() {
			$namespace = "urn:ietf:params:xml:ns:epp-1.0";
			$ext_package_ns = "http://www.heartinternet.co.uk/whapi/ext-package-2.2";
			$ext_whapi_ns = "http://www.heartinternet.co.uk/whapi/ext-whapi-2.0";
			$doc = new DOMDocument(); $l = $doc;
			$c = $doc->createElementNS($namespace, 'epp');
			$l->appendChild($c); $l = $c;
			$c = $doc->createElementNS($namespace, 'extension');
			$l->appendChild($c); $l = $c;
			$c = $doc->createElementNS($ext_package_ns, "list");
			$l->appendChild($c);
			$c = $doc->createElementNS($ext_whapi_ns, "clTRID");
			$l->appendChild($c);
			$c->appendChild($doc->createTextNode('b57b7ac295ac79664fe5176761b35529'));
			$l = $l->parentNode;
			$l = $l->parentNode;
			$output = $doc->saveXML();

			$returned_xml = $this->send($output, true);
			$xml = simplexml_load_string($returned_xml);

			$packages = [];
			foreach($xml->response->resData->children('http://www.heartinternet.co.uk/whapi/ext-package-2.2')->lstData->package as $package) {
				$packages[] = ['id' => $package->id->__toString(), 'domainName' => $package->domainName->__toString()];
			}
			return $packages;
		}

		public function getPackage( $packageID ) {
			$namespace      = "urn:ietf:params:xml:ns:epp-1.0";
			$package_ns     = "http://www.heartinternet.co.uk/whapi/package-2.2";
			$ext_package_ns = "http://www.heartinternet.co.uk/whapi/ext-package-2.2";
			$doc            = new DOMDocument();
			$l              = $doc;
			$c              = $doc->createElementNS( $namespace, 'epp' );
			$l->appendChild( $c );
			$l = $c;
			$c = $doc->createElementNS( $namespace, 'command' );
			$l->appendChild( $c );
			$l = $c;
			$c = $doc->createElementNS( $namespace, 'info' );
			$l->appendChild( $c );
			$l = $c;
			$c = $doc->createElementNS( $package_ns, "info" );
			$l->appendChild( $c );
			$l = $c;
			$c = $doc->createElementNS( $package_ns, "id" );
			$l->appendChild( $c );
			$c->appendChild( $doc->createTextNode( $packageID ) );
			$l = $l->parentNode;
			$l = $l->parentNode;
			$c = $doc->createElementNS( $namespace, 'extension' );
			$l->appendChild( $c );
			$l = $c;
			$c = $doc->createElementNS( $ext_package_ns, "info" );
			$l->appendChild( $c );
			$l = $c;
			$c = $doc->createElementNS( $ext_package_ns, "detail" );
			$l->appendChild( $c );
			$l = $c;
			$c = $doc->createElementNS( $ext_package_ns, "database" );
			$l->appendChild( $c );
			$c->appendChild( $doc->createTextNode( 'mailbox' ) );
			$l = $l->parentNode;
			$l = $l->parentNode;
			$c = $doc->createElementNS( $namespace, 'clTRID' );
			$l->appendChild( $c );
			$c->appendChild( $doc->createTextNode( 'bbb5a214d249f7d923e4790fd342ce89' ) );
			$l      = $l->parentNode;
			$l      = $l->parentNode;
			$output = $doc->saveXML();

			$returned_xml = $this->send( $output, true );
			$xml          = simplexml_load_string( $returned_xml );

			foreach ( $xml->response->children( '', 'result' ) as $child ) {
				if ( $child->attributes()->code == 2303 ) {
					return false;
				}
			}

			return [
				'package'   => $xml->response->resData->children( 'http://www.heartinternet.co.uk/whapi/package-2.2' )->infData,
				'extension' => $xml->response->extension->children( 'http://www.heartinternet.co.uk/whapi/ext-package-2.2' )->infData
			];
		}

		public function addDomainToPackage($domains, $packageID) {
			$namespace = "urn:ietf:params:xml:ns:epp-1.0";
			$package_ns = "http://www.heartinternet.co.uk/whapi/package-2.2";
			$doc = new DOMDocument(); $l = $doc;
			$c = $doc->createElementNS($namespace, 'epp');
			$l->appendChild($c); $l = $c;
			$c = $doc->createElementNS($namespace, 'command');
			$l->appendChild($c); $l = $c;
			$c = $doc->createElementNS($namespace, 'update');
			$l->appendChild($c); $l = $c;
			$c = $doc->createElementNS($package_ns, "update");
			$l->appendChild($c); $l = $c;
			$c = $doc->createElementNS($package_ns, "id");
			$l->appendChild($c);
			$c->appendChild($doc->createTextNode($packageID));
			$c = $doc->createElementNS($package_ns, "add");
			$l->appendChild($c); $l = $c;
			foreach($domains as $domain) {
				$c = $doc->createElementNS($package_ns, "domainName");
				$l->appendChild($c);
				$c->appendChild($doc->createTextNode($domain));
			}
			$l = $l->parentNode;
			$l = $l->parentNode;
			$l = $l->parentNode;
			$c = $doc->createElementNS($namespace, 'clTRID');
			$l->appendChild($c);
			$c->appendChild($doc->createTextNode('405381be6b1feb90a5e1c98071289111'));
			$l = $l->parentNode;
			$l = $l->parentNode;
			$output = $doc->saveXML();

			$returned_xml = $this->send($output, true);
			$xml = simplexml_load_string($returned_xml);
		}

		public function removeDomainFromPackage( $domain, $packageID ) {
			$namespace = "urn:ietf:params:xml:ns:epp-1.0";
			$package_ns = "http://www.heartinternet.co.uk/whapi/package-2.2";
			$doc = new DOMDocument(); $l = $doc;
			$c = $doc->createElementNS($namespace, 'epp');
			$l->appendChild($c); $l = $c;
			$c = $doc->createElementNS($namespace, 'command');
			$l->appendChild($c); $l = $c;
			$c = $doc->createElementNS($namespace, 'update');
			$l->appendChild($c); $l = $c;
			$c = $doc->createElementNS($package_ns, "update");
			$l->appendChild($c); $l = $c;
			$c = $doc->createElementNS($package_ns, "id");
			$l->appendChild($c);
			$c->appendChild($doc->createTextNode($packageID));
			$c = $doc->createElementNS($package_ns, "rem");
			$l->appendChild($c); $l = $c;
			$c = $doc->createElementNS($package_ns, "domainName");
			$l->appendChild($c);
			$c->appendChild($doc->createTextNode($domain));
			$l = $l->parentNode;
			$l = $l->parentNode;
			$l = $l->parentNode;
			$c = $doc->createElementNS($namespace, 'clTRID');
			$l->appendChild($c);
			$c->appendChild($doc->createTextNode('b096442a6dba07e48a387010158d47e4'));
			$l = $l->parentNode;
			$l = $l->parentNode;
			$output = $doc->saveXML();
			$returned_xml = $this->send($output, true);
			$xml = simplexml_load_string($returned_xml);
		}

		public function createExchangeMailbox($emailAddress) {
			$namespace = "urn:ietf:params:xml:ns:epp-1.0";
			$mailbox_ns = "http://www.heartinternet.co.uk/whapi/mailbox-2.0";
			$doc = new DOMDocument(); $l = $doc;
			$c = $doc->createElementNS($namespace, 'epp');
			$l->appendChild($c); $l = $c;
			$c = $doc->createElementNS($namespace, 'command');
			$l->appendChild($c); $l = $c;
			$c = $doc->createElementNS($namespace, 'create');
			$l->appendChild($c); $l = $c;
			$c = $doc->createElementNS($mailbox_ns, "create");$c->setAttribute('maxCharge', "50.00");
			$l->appendChild($c); $l = $c;
			$c = $doc->createElementNS($mailbox_ns, "emailAddress");
			$l->appendChild($c);
			$c->appendChild($doc->createTextNode($emailAddress));
			$c = $doc->createElementNS($mailbox_ns, "type");
			$l->appendChild($c);
			$c->appendChild($doc->createTextNode('exchange'));
			$c = $doc->createElementNS($mailbox_ns, "features");
			$l->appendChild($c); $l = $c;
			$c = $doc->createElementNS($mailbox_ns, "exchangeFeatures");
			$l->appendChild($c);
			$c->appendChild($doc->createTextNode('blackberryServices'));
			$l = $l->parentNode;
			$l = $l->parentNode;
			$l = $l->parentNode;
			$c = $doc->createElementNS($namespace, 'clTRID');
			$l->appendChild($c);
			$c->appendChild($doc->createTextNode('58774c08dd9444807bb1327095b0de63'));
			$l = $l->parentNode;
			$l = $l->parentNode;
			$output = $doc->saveXML();

			$returned_xml = $this->send($output, true);
			$xml = simplexml_load_string($returned_xml);
			$code = $xml->response->result->attributes()->code;
			if($code == 1000) {
				return true;
			} else {
				return $code;
			}
		}

		public function createPackage($domain, $email, $name, $type) {
			$namespace = "urn:ietf:params:xml:ns:epp-1.0";
			$package_ns = "http://www.heartinternet.co.uk/whapi/package-2.2";
			$doc = new DOMDocument(); $l = $doc;
			$c = $doc->createElementNS($namespace, 'epp');
			$l->appendChild($c); $l = $c;
			$c = $doc->createElementNS($namespace, 'command');
			$l->appendChild($c); $l = $c;
			$c = $doc->createElementNS($namespace, 'create');
			$l->appendChild($c); $l = $c;
			$c = $doc->createElementNS($package_ns, "create");
			$l->appendChild($c); $l = $c;
			$c = $doc->createElementNS($package_ns, "domainName");$c->setAttribute('mustCreate', "1");
			$l->appendChild($c);
			$c->appendChild($doc->createTextNode($domain));
			$c = $doc->createElementNS($package_ns, "emailAddress");$c->setAttribute('name', $name);
			$l->appendChild($c);
			$c->appendChild($doc->createTextNode($email));
			$c = $doc->createElementNS($package_ns, "type");
			$l->appendChild($c);
			$c->appendChild($doc->createTextNode($type));
			$l = $l->parentNode;
			$l = $l->parentNode;
			$c = $doc->createElementNS($namespace, 'clTRID');
			$l->appendChild($c);
			$c->appendChild($doc->createTextNode('974c756193ea5c2a309ec8ea1df87a89'));
			$l = $l->parentNode;
			$l = $l->parentNode;
			$output = $doc->saveXML();

			$returned_xml = $this->send($output, true);
			$xml = simplexml_load_string($returned_xml);
			return $xml;
		}

		public function getPackageTypes() {
			$namespace = "urn:ietf:params:xml:ns:epp-1.0";
			$ext_package_ns = "http://www.heartinternet.co.uk/whapi/ext-package-2.2";
			$ext_whapi_ns = "http://www.heartinternet.co.uk/whapi/ext-whapi-2.0";
			$doc = new DOMDocument(); $l = $doc;
			$c = $doc->createElementNS($namespace, 'epp');
			$l->appendChild($c); $l = $c;
			$c = $doc->createElementNS($namespace, 'extension');
			$l->appendChild($c); $l = $c;
			$c = $doc->createElementNS($ext_package_ns, "listTypes");
			$l->appendChild($c);
			$c = $doc->createElementNS($ext_whapi_ns, "clTRID");
			$l->appendChild($c);
			$c->appendChild($doc->createTextNode('82562e1830f07de8e8913cb894efd6b5'));
			$l = $l->parentNode;
			$l = $l->parentNode;
			$output = $doc->saveXML();

			$returned_xml = $this->send($output, true);
			$xml = simplexml_load_string($returned_xml);
			$packages = [];
			foreach($xml->response->resData->children($ext_package_ns)->lstData->packageType as $package) {
				$attributes = $package->attributes();
				$packages[$attributes->id->__toString()] = ['name' => $package->__toString(), 'serverType' => $attributes->serverType->__toString()];
			}
			return $packages;
		}
		
		public function createNewContact($firstName, $surname, $addStreet1,$addStreet2,$addCity,$postCode, $countryCode, $phone, $email, $gender, $salutation, $dob) {
		  $namespace = "urn:ietf:params:xml:ns:epp-1.0";
  $ext_contact_ns = "http://www.heartinternet.co.uk/whapi/ext-contact-2.0";
  $contact_ns = "urn:ietf:params:xml:ns:contact-1.0";
  $doc = new DOMDocument(); $l = $doc;
  $c = $doc->createElementNS($namespace, 'epp');
  $l->appendChild($c); $l = $c;
    $c = $doc->createElementNS($namespace, 'command');
    $l->appendChild($c); $l = $c;
      $c = $doc->createElementNS($namespace, 'create');
      $l->appendChild($c); $l = $c;
        $c = $doc->createElementNS($contact_ns, "create");
        $l->appendChild($c); $l = $c;
          $c = $doc->createElementNS($contact_ns, "id");
          $l->appendChild($c);
          $c->appendChild($doc->createTextNode('IGNORED'));
          $c = $doc->createElementNS($contact_ns, "postalInfo");$c->setAttribute('type', "loc"); 
          $l->appendChild($c); $l = $c;
            $c = $doc->createElementNS($contact_ns, "name");
            $l->appendChild($c);
            $c->appendChild($doc->createTextNode($firstName));
            $c = $doc->createElementNS($contact_ns, "addr");
            $l->appendChild($c); $l = $c;
              $c = $doc->createElementNS($contact_ns, "street");
              $l->appendChild($c);
              $c->appendChild($doc->createTextNode($addStreet1));
              $c = $doc->createElementNS($contact_ns, "street");
              $l->appendChild($c);
              $c->appendChild($doc->createTextNode($addStreet2));
              $c = $doc->createElementNS($contact_ns, "city");
              $l->appendChild($c);
              $c->appendChild($doc->createTextNode($addCity));
              $c = $doc->createElementNS($contact_ns, "pc");
              $l->appendChild($c);
              $c->appendChild($doc->createTextNode($postCode));
              $c = $doc->createElementNS($contact_ns, "cc");
              $l->appendChild($c);
              $c->appendChild($doc->createTextNode($countryCode));
            $l = $l->parentNode;
          $l = $l->parentNode;
          $c = $doc->createElementNS($contact_ns, "voice");
          $l->appendChild($c);
          $c->appendChild($doc->createTextNode($phone));
          $c = $doc->createElementNS($contact_ns, "email");
          $l->appendChild($c);
          $c->appendChild($doc->createTextNode($email));
          $c = $doc->createElementNS($contact_ns, "authInfo");
          $l->appendChild($c); $l = $c;
            $c = $doc->createElementNS($contact_ns, "ext");
            $l->appendChild($c); $l = $c;
              $c = $doc->createElementNS($ext_contact_ns, "null");
              $l->appendChild($c);
            $l = $l->parentNode;
          $l = $l->parentNode;
        $l = $l->parentNode;
      $l = $l->parentNode;
      $c = $doc->createElementNS($namespace, 'extension');
      $l->appendChild($c); $l = $c;
        $c = $doc->createElementNS($ext_contact_ns, "createExtension");
        $l->appendChild($c); $l = $c;
          $c = $doc->createElementNS($ext_contact_ns, "person");
          $l->appendChild($c); $l = $c;
            $c = $doc->createElementNS($ext_contact_ns, "salutation");$c->setAttribute('gender', $gender); 
            $l->appendChild($c);
            $c->appendChild($doc->createTextNode($salutation));
            $c = $doc->createElementNS($ext_contact_ns, "surname");
            $l->appendChild($c);
            $c->appendChild($doc->createTextNode($surname));
            $c = $doc->createElementNS($ext_contact_ns, "otherNames");
            $l->appendChild($c);
            $c->appendChild($doc->createTextNode($firstName));
            $c = $doc->createElementNS($ext_contact_ns, "dateOfBirth");
            $l->appendChild($c);
            $c->appendChild($doc->createTextNode($dob));
          $l = $l->parentNode;
          $c = $doc->createElementNS($ext_contact_ns, "organisationType");
          $l->appendChild($c);
          $c->appendChild($doc->createTextNode('company'));
          $c = $doc->createElementNS($ext_contact_ns, "telephone");$c->setAttribute('type', "mobile"); 
          $l->appendChild($c);
          $c->appendChild($doc->createTextNode($phone));
        $l = $l->parentNode;
      $l = $l->parentNode;
      $c = $doc->createElementNS($namespace, 'clTRID');
      $l->appendChild($c);
      $c->appendChild($doc->createTextNode('71a5c8e557f579017b27b49879281db3'));
    $l = $l->parentNode;
  $l = $l->parentNode; 
  $output = $doc->saveXML();
		$output = $doc->saveXML();

		$returned_xml = $this->send( $output, true );
		file_put_contents( 'domainIN.xml', $output );
		file_put_contents( 'domain.xml', $returned_xml );
		$xml = simplexml_load_string( $returned_xml );

		return $xml;
	}

		public function regstierDomain( $domainName, $years, $registrantID, $password) {
			$namespace = "urn:ietf:params:xml:ns:epp-1.0";
			$ext_domain_ns = "http://www.heartinternet.co.uk/whapi/ext-domain-2.5";
			$domain_ns = "urn:ietf:params:xml:ns:domain-1.0";
			$doc = new DOMDocument(); $l = $doc;
			$c = $doc->createElementNS($namespace, 'epp');
			$l->appendChild($c); $l = $c;
			$c = $doc->createElementNS($namespace, 'command');
			$l->appendChild($c); $l = $c;
			$c = $doc->createElementNS($namespace, 'create');
			$l->appendChild($c); $l = $c;
			$c = $doc->createElementNS($domain_ns, "create");
			$l->appendChild($c); $l = $c;
			$c = $doc->createElementNS($domain_ns, "name");
			$l->appendChild($c);
			$c->appendChild($doc->createTextNode($domainName));
			$c = $doc->createElementNS($domain_ns, "period");$c->setAttribute('unit', "y");
			$l->appendChild($c);
			$c->appendChild($doc->createTextNode($years));
			$c = $doc->createElementNS($domain_ns, "registrant");
			$l->appendChild($c);
			$c->appendChild($doc->createTextNode($registrantID));
			$c = $doc->createElementNS($domain_ns, "authInfo");
			$l->appendChild($c); $l = $c;
			$c = $doc->createElementNS($domain_ns, "pw");
			$l->appendChild($c);
			$c->appendChild($doc->createTextNode($password));
			$l = $l->parentNode;
			$l = $l->parentNode;
			$l = $l->parentNode;
			$c = $doc->createElementNS($namespace, 'extension');
			$l->appendChild($c); $l = $c;
			$c = $doc->createElementNS($ext_domain_ns, "createExtension");
			$l->appendChild($c); $l = $c;
			$c = $doc->createElementNS($ext_domain_ns, "privacy");
			$l->appendChild($c);
			$c = $doc->createElementNS($ext_domain_ns, "registrationMechanism");
			$l->appendChild($c);
			$c->appendChild($doc->createTextNode('credits'));
			$c = $doc->createElementNS($ext_domain_ns, "registrationMechanism");
			$l->appendChild($c);
			$c->appendChild($doc->createTextNode('basket'));
			$c = $doc->createElementNS($ext_domain_ns, "registrantSecurityDetails");
			$l->appendChild($c); $l = $c;
			$c = $doc->createElementNS($ext_domain_ns, "securityChallenge");$c->setAttribute('question', "question1");
			$l->appendChild($c);
			$c->appendChild($doc->createTextNode('What is your mother\'s maiden name'));
			$c = $doc->createElementNS($ext_domain_ns, "securityChallenge");$c->setAttribute('question', "question2");
			$l->appendChild($c);
			$c->appendChild($doc->createTextNode('What town were you born in'));
			$c = $doc->createElementNS($ext_domain_ns, "securityChallenge");$c->setAttribute('question', "question3");
			$l->appendChild($c);
			$c->appendChild($doc->createTextNode('What was the name of your first pet'));
			$l = $l->parentNode;
			$l = $l->parentNode;
			$l = $l->parentNode;
			$c = $doc->createElementNS($namespace, 'clTRID');
			$l->appendChild($c);
			$c->appendChild($doc->createTextNode('9624ed7007dfea9a81674193af21169e'));
			$l = $l->parentNode;
			$l = $l->parentNode;
			$output = $doc->saveXML();

			$returned_xml = $this->send($output, true);
			$xml = simplexml_load_string($returned_xml);
			return $xml;			
		}
		
		
		public function listdomains() {
			$namespace = "urn:ietf:params:xml:ns:epp-1.0";
  $ext_domain_ns = "http://www.heartinternet.co.uk/whapi/ext-domain-2.5";
  $domain_ns = "urn:ietf:params:xml:ns:domain-1.0";
  $ext_whapi_ns = "http://www.heartinternet.co.uk/whapi/ext-whapi-2.0";
  $doc = new DOMDocument(); $l = $doc;
  $c = $doc->createElementNS($namespace, 'epp');
  $l->appendChild($c); $l = $c;
    $c = $doc->createElementNS($namespace, 'extension');
    $l->appendChild($c); $l = $c;
      $c = $doc->createElementNS($ext_domain_ns, "list");$c->setAttribute('purpose', "manage"); 
      $l->appendChild($c);
      $c = $doc->createElementNS($ext_whapi_ns, "clTRID");
      $l->appendChild($c);
      $c->appendChild($doc->createTextNode('cff2cad609661333bad93296ecdd60c7'));
    $l = $l->parentNode;
  $l = $l->parentNode; 
  $output = $doc->saveXML();
  			$returned_xml = $this->send($output, true);
			$xml = simplexml_load_string($returned_xml);
			
			
		file_put_contents( 'domainIN.xml', $output );
		file_put_contents( 'domain.xml', $returned_xml );
			
			return $xml;
  			
		}

		public function getPanelLoginUrl($packageID) {
			$namespace = "urn:ietf:params:xml:ns:epp-1.0";
			$package_ns = "http://www.heartinternet.co.uk/whapi/package-2.2";
			$ext_package_ns = "http://www.heartinternet.co.uk/whapi/ext-package-2.2";
			$doc = new DOMDocument(); $l = $doc;
			$c = $doc->createElementNS($namespace, 'epp');
			$l->appendChild($c); $l = $c;
			$c = $doc->createElementNS($namespace, 'command');
			$l->appendChild($c); $l = $c;
			$c = $doc->createElementNS($namespace, 'info');
			$l->appendChild($c); $l = $c;
			$c = $doc->createElementNS($package_ns, "info");
			$l->appendChild($c); $l = $c;
			$c = $doc->createElementNS($package_ns, "id");
			$l->appendChild($c);
			$c->appendChild($doc->createTextNode($packageID));
			$l = $l->parentNode;
			$l = $l->parentNode;
			$c = $doc->createElementNS($namespace, 'extension');
			$l->appendChild($c); $l = $c;
			$c = $doc->createElementNS($ext_package_ns, "preAuthenticate");
			$l->appendChild($c);
			$l = $l->parentNode;
			$c = $doc->createElementNS($namespace, 'clTRID');
			$l->appendChild($c);
			$c->appendChild($doc->createTextNode('fac89208bea460fa3fef11b22a519cce'));
			$l = $l->parentNode;
			$l = $l->parentNode;
			$output = $doc->saveXML();

			$returned_xml = $this->send($output, true);
			$xml = simplexml_load_string($returned_xml);
			$code = $xml->response->result->attributes()->code;
			if($code == 2304) {
				return false;
			} else {
				return $xml->response->resData->children( 'http://www.heartinternet.co.uk/whapi/ext-package-2.2' )->redirectURL->__toString();
			}
		}

		public function send($input, $parse) {
			try {
				$output = $this->hi_api->sendMessage( $input, $parse );
				file_put_contents(__DIR__ . '/test.xml', $output);
				return $output;
			} catch ( Exception $e ) {
				return $e;
			}

		}

		public function logout() {
			$namespace = "urn:ietf:params:xml:ns:epp-1.0";
			$doc = new DOMDocument(); $l = $doc;
			$c = $doc->createElementNS($namespace, 'epp');
			$l->appendChild($c); $l = $c;
			$c = $doc->createElementNS($namespace, 'command');
			$l->appendChild($c); $l = $c;
			$c = $doc->createElementNS($namespace, 'logout');
			$l->appendChild($c);
			$c = $doc->createElementNS($namespace, 'clTRID');
			$l->appendChild($c);
			$c->appendChild($doc->createTextNode('90908b2caabbb97c1e79899816efc093'));
			$l = $l->parentNode;
			$l = $l->parentNode;
			$output = $doc->saveXML();
			$this->send($output, true);
		}



	}
