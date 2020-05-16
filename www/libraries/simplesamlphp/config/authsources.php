<?php
$config = array (
  'admin' => 
  array (
    0 => 'core:AdminPassword',
  ),
  'identity-provider' => 
  array (
    0 => 'saml:SP',
    'idp' => 'https://identity.joomla.org/www/sso/saml2/idp/metadata.php',
    'discoURL' => NULL,
    'NameIDPolicy' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified',
    'privatekey' => 'volunteers.pem',
    'certificate' => 'volunteers.crt',
    'sign.logout' => true,
    'signature.algorithm' => 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256',
    'SingleLogoutServiceBinding' => 
    array (
      0 => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
      1 => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
      2 => 'urn:oasis:names:tc:SAML:2.0:bindings:SOAP',
    ),
    'attributes' => 
    array (
      'Name' => 'name',
      'E-Mail Address' => 'emailaddress',
      'Username' => 'upn',
    ),
    'attributes.NameFormat' => 'urn:oasis:names:tc:SAML:2.0:attrname-format:basic',
    'attributes.required' => 
    array (
      0 => 'name',
      1 => 'emailaddress',
      2 => 'upn',
    ),
  ),
);