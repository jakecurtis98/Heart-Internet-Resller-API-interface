<?php

	class tinyMailer {

		public  $host;
		public  $port;
		public  $user;
		public  $to;
		public  $from;
		public  $fromName;
		public  $bcc;
		public  $body;
		public  $subject;
		public  $debug = false;
		private $password;

		public function __construct($host, $port, $user, $password) {
			$this->host = $host;
			$this->port = $port;
			$this->user = $user;
			$this->password = $password;
			$this->debug($this->host);
		}

		public function connect() {
			$ctx = stream_context_create();
			stream_context_set_option( $ctx, 'ssl', 'verify_peer', false );
			stream_context_set_option( $ctx, 'ssl', 'verify_peer_name', false );
			stream_context_set_option( $ctx, 'ssl', 'allow_self_signed', true );
			$socket = stream_socket_client( 'tcp://' . $this->host . ':' . $this->port, $err, $errstr, 10, STREAM_CLIENT_CONNECT, $ctx );
			if ( ! $socket ) {
				print "Failed to connect $err $errstr\n";
				return false;
			} else {
				return $socket;
			}
		}

		public function debug( $content ) {
			if($this->debug) {
				echo "<pre>";
				var_dump(htmlspecialchars($content));
				echo "</pre>";
			}
		}

		public function checkVars() {
			if(isset($this->host, $this->port, $this->user, $this->to, $this->from, $this->body, $this->subject, $this->password, $this->fromName)) {
				if(!is_array($this->to)) {
					$this->to = [$this->to];
				}
				if(!is_array($this->bcc)) {
					$this->bcc = [$this->bcc];
				}
				return true;
			} else {
				return false;
			}
		}

		public function AddAddress( $address ) {
			if($this->to == null) {
				$this->to = [$address];
			} elseif(is_array($this->to)) {
				$this->to[] = $address;
			} else {
				$this->to = [$this->to, $address];
			}
		}

		public function send() {

			if ( $this->checkVars()) {
				$socket = $this->connect();
				if ( $socket !== false ) {
					$this->read( $socket, 8192 );

					$this->write( $socket, "EHLO " . $this->host . "\r\n" );
					$this->read( $socket, 8192 );

					// Start tls connection
					$this->write( $socket, "STARTTLS\r\n" );
					$this->read( $socket, 8192 );

					stream_socket_enable_crypto( $socket, true, STREAM_CRYPTO_METHOD_SSLv23_CLIENT );

					// Send ehlo
					$this->write( $socket, "EHLO " . $this->host . "\r\n" );
					$this->read( $socket, 8192 );

					// start login
					$this->write( $socket, "AUTH LOGIN\r\n" );
					$this->read( $socket, 8192 );
					$this->debug("Username: " . $this->user);
					$this->write( $socket, base64_encode( $this->user ) . "\r\n" );
					$this->read( $socket, 8192 );
					$this->debug("Password: " . $this->password);
					$this->write( $socket, base64_encode( $this->password ) . "\r\n" );
					$this->read( $socket, 8192 );

					$this->write( $socket, "MAIL FROM: " . $this->from . "\r\n" );
					$this->read( $socket, 8192 );

					foreach($this->to as $address) {
						$this->write( $socket, "rcpt to: <" . trim($address) . ">\r\n" );
						$this->read( $socket, 8192 );
					}

					foreach($this->bcc as $address) {
						$this->write( $socket, "rcpt to: <" . trim($address) . ">\r\n" );
						$this->read( $socket, 8192 );
					}

					//$this->write( $socket, "rcpt bcc: " . implode(", ", $this->bcc) . "\r\n" );
					//$this->read( $socket, 8192 );

					$this->write( $socket, "DATA\n" );
					$this->read( $socket, 8192 );

					$this->write( $socket, "Date: " . date("D, d M Y H:i:s O") . "\r\nTo: " . implode(", ", $this->to) . "\r\nFrom: " . $this->fromName . "<" . $this->from . ">\r\nSubject: " . $this->subject . "\r\nMIME-Version: 1.0\r\nContent-type: text/html; charset=iso-8859-1\r\n" . $this->body . "\r\n.\r\n" );
					$this->read( $socket, 8192 );

					$this->write( $socket, "QUIT \n" );
					$this->read( $socket, 8192 );

					fclose($socket);
					return true;
				} else {
					print "Failed to connect";
				}
			} else {
				print "Make sure all vars are set";
			}
		}

		public function write( $socket, $content ) {
			$this->debug($content);
			fwrite($socket, $content);
		}

		public function read( $handle, $length) {
			$this->debug(fread($handle, $length));
		}

	}
