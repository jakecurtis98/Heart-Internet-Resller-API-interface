<?php
	define('base_url', 'http://localhost/heart');
	require_once 'matmHeart.class.php';
	require_once 'credentials.php';

	$matm = new matmHeart($username, $password);

	$objects = [
        "http://www.heartinternet.co.uk/whapi/database-2.0",
        "http://www.heartinternet.co.uk/whapi/mailbox-2.0",
        "http://www.heartinternet.co.uk/whapi/offsite-package-2.2",
        "http://www.heartinternet.co.uk/whapi/server-2.2",
        "http://www.heartinternet.co.uk/whapi/support-2.0",
		"http://www.heartinternet.co.uk/whapi/package-2.2",
		"http://www.heartinternet.co.uk/whapi/null-2.0",
		"urn:ietf:params:xml:ns:contact-1.0",
		"urn:ietf:params:xml:ns:domain-1.0",

	];
	$extensions = [
		"http://www.heartinternet.co.uk/whapi/ext-antivirus-2.0",
		"http://www.heartinternet.co.uk/whapi/ext-billing-2.0",
		"http://www.heartinternet.co.uk/whapi/ext-contact-2.0",
		"http://www.heartinternet.co.uk/whapi/ext-database-2.0",
		"http://www.heartinternet.co.uk/whapi/ext-dns-2.0",
		"http://www.heartinternet.co.uk/whapi/ext-domain-2.5",
		"http://www.heartinternet.co.uk/whapi/ext-host-2.0",
		"http://www.heartinternet.co.uk/whapi/ext-mailbox-2.0",
		"http://www.heartinternet.co.uk/whapi/ext-null-2.0",
		"http://www.heartinternet.co.uk/whapi/ext-offsite-package-2.2",
		"http://www.heartinternet.co.uk/whapi/ext-package-2.2",
		"http://www.heartinternet.co.uk/whapi/ext-security-2.0",
		"http://www.heartinternet.co.uk/whapi/ext-server-2.2",
		"http://www.heartinternet.co.uk/whapi/ext-support-2.0",
		"http://www.heartinternet.co.uk/whapi/ext-wbp-2.0",
		"http://www.heartinternet.co.uk/whapi/ext-whapi-2.0",
	];

	$matm->login($objects, $extensions, false);
	// $domainContact = $matm->createNewContact('Jacob','Tilsley-Curtis','3 Coney Green Way','Wellington','Telford','TF13QZ', 'GB', '+44.7375660699', 'topcat543@gmail.com', 'male', 'Mr', '1998-02-02');
// 	$contactID = $domainContact->response->resData->children("urn:ietf:params:xml:ns:contact-1.0")->creData->id->__toString();
// 	
// 	echo "<pre>";
// 	var_dump($matm->regstierDomain( 'www.thisisatest123452245522.co.uk', 1, $contactID, 'bethany02'));
// 	echo "</pre>";
// 	
// 	echo "<pre>";
// 	var_dump($matm->listdomains());
// 	echo "<pre>";	
// 	exit;
	if(!isset($_GET['package']) && !isset($_GET['action'])) {
		?>
		<form method="GET" action="<?=base_url?>">
			<h3>Create new package</h3>
            <input type="hidden" name="action" value="createPackage" />
			<label>Domain: <input type="text" name="domain" value=""/></label>
			<label>Email: <input type="text" name="email" value=""/></label>
			<label>Name: <input type="text" name="name" value=""/></label>
            <label>Type:
                <select name="type">
                    <?php foreach($matm->getPackageTypes() as $id => $package_type) {
                        ?><option value="<?=$id?>"><?=$package_type['name']?></option><?php
                    } ?>
                </select></label>
			<input type="submit">
		</form>
		<?php
		$packages = $matm->getPackages();
		print "<ul>";
		foreach ( $packages as $package ) {
			print "<li style='list-style: none'>";
			print "<a href='?package=" . $package['id'] . "'><h1>" . $package['domainName'] . "</h1></a>";
			$packageInfo = $matm->getPackage($package['id']);
			echo "<pre>";
			//var_dump($packageInfo);
			echo "</pre>";
			print "<ul>";
			foreach($packageInfo['package']->detail->server as $server) {
				print "<li>" . ($server->attributes()->role == "web" ? $server->attributes()->role . " (" . $server->attributes()->type . ") " : $server->attributes()->role ) . ": " . $server . "</li>";
			}
			print "</ul>";
			print "<br>Email Accounts: " . count($packageInfo['extension']->mailboxes->mailbox);

			print "<h2>Usage:</h2>";
			print "<ul>";
			$used = $packageInfo['package']->counters->webSpace;
			$limit = $packageInfo['package']->limits->site->webSpace;
			print "<li style='" . ($used >= $limit ? "color: red;" : "") . "'>Web Space: " . $used . "MB / " . $limit . "MB</li>";
			foreach($packageInfo['package']->counters->bandwidth as $bandwidth) {
				$scope = str_replace( '-', ' ', $bandwidth->attributes()->scope->__toString());
				$limit = $packageInfo['package']->limits->site->bandwidth;
				if($scope == "last hour") {
					print "<li>Bandwidth (" . $scope . "): " . $bandwidth . "</li>";
				} else {
					print "<li style='" . ($bandwidth >= $limit ? "color: red;" : "") . "'>Bandwidth (" . $scope . "): " . $bandwidth . "MB / " . $limit . "MB</li>";
				}
			}
			print "</ul>";
			print "</li><br><br><br><br>";
		}
		print "</ul>";
	} elseif(isset($_GET['package']) && !isset($_GET['action'])) {
		$packageID = $_GET['package'];
		if(isset($_GET['domains'])) {
			$domains = explode(',', urldecode($_GET['domains']));
			$matm->addDomainToPackage($domains, $packageID);
		} elseif (isset($_GET['remdomain'])) {
			$matm->removeDomainFromPackage($_GET['remdomain'], $packageID);
		}
		$package = $matm->getPackage($packageID);
		?>
		<h1><?=$package['package']->domainName?></h1>
		<p><a href="<?= base_url ?>"><- Back</a></p>
		<form method="GET" action="<?=base_url?>">
			<input type="hidden" name="package" value="<?=$packageID?>"/>
			<label>Add Domain to package: <input type="text" name="domains" value=""/></label>
			<input type="submit">
		</form>
		<h2>Servers:</h2>
		<ul>
			<?php
			foreach($package['package']->detail->server as $server) {
				print "<li>" . ($server->attributes()->role == "web" ? $server->attributes()->role . " (" . $server->attributes()->type . ") " : $server->attributes()->role )  . ": " . $server . "</li>";
			}
			?>
		</ul>

		<h2>Usage:</h2>
		<ul>
			<li>Web Space: <?=$package['package']->counters->webSpace?>MB / <?=$package['package']->limits->site->webSpace?></li>
			<?php
				foreach($package['package']->counters->bandwidth as $bandwidth) {
					$scope = str_replace( '-', ' ', $bandwidth->attributes()->scope->__toString());
					$limit = $package['package']->limits->site->bandwidth;
					if($scope == "last hour") {
						print "<li>Bandwidth (" . $scope . "): " . $bandwidth . "</li>";
					} else {
						print "<li style='" . ($bandwidth >= $limit ? "color: red;" : "") . "'>Bandwidth (" . $scope . "): " . $bandwidth . "MB / " . $limit . "MB</li>";
					}
				}
			?>
		</ul>

		<h2>Domains:</h2>
		<ul>
			<?php foreach($domain = $package['package']->domainName as $domain) {
				?><li><?=$domain?><a href="<?=base_url?>/?package=<?=$packageID?>&remdomain=<?=$domain?>"> Remove Domain</a></li><?php
			} ?>
		</ul>

		<h2>Mailboxes:</h2>
		<ul>
			<?php
				foreach($package['extension']->mailboxes->mailbox as $mailbox) {
					print "<li>" . $mailbox->emailAddress . " - " . $mailbox->attributes()->type . "</li>";
				}
			?>
		</ul>

		<?php

		echo "<pre>";
		var_dump($package);
		echo "</pre>";
	} elseif(isset($_GET['action'])) {
		if($_GET['action'] == "createPackage") {
			$matm->createPackage($_GET['domain'], $_GET['email'], $_GET['name'], $_GET['type'] );
		} elseif($_GET['action'] == "panelLogin") {
		    $url = $matm->getPanelLoginUrl($_GET['packageID']);
			header('Location: ' . $url);
		    exit;
        } elseif($_GET['action'] == "listPackages") {
			echo "<pre>";
			var_dump($matm->getPackageTypes());
			echo "</pre>";
		}
	}

	//$matm->logout();