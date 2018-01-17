<?php

namespace RCS\Git;

class Server extends \Sabre\DAV\Server {

    protected $_plugins = array();

    public function __construct()
    {
        // parent::__construct();
    }
    
    public function addServerPlugin(\Sabre\DAV\ServerPlugin $serverPlugin)
    {
        $this->_plugins[] = $serverPlugin;
    }
    
    public function webdav($arguments)
    {
        // http://sabre.io/dav/gettingstarted/
        // Now we're creating a whole bunch of objects
        // This is done here as it's specific to the repository.
        $rootDirectory = new \Sabre\DAV\FS\Directory(REPOSITORY_PATH . DIRECTORY_SEPARATOR . "{$arguments["repository"]}.git/");

        // The server object is responsible for making sense out of the WebDAV protocol
        // $server = new \Sabre\DAV\Server($rootDirectory);
        parent::__construct($rootDirectory);

        // If your server is not on your webroot, make sure the following line has the
        // correct information
        $this->setBaseUri("/{$arguments["repository"]}.git");

        // The lock manager is reponsible for making sure users don't overwrite
        // each others changes. 
        // This is done here as it's specific to the repository.
        $lockBackend = new \Sabre\DAV\Locks\Backend\File(REPOSITORY_PATH . DIRECTORY_SEPARATOR . "{$arguments["repository"]}.git/locks");
        $lockPlugin = new \Sabre\DAV\Locks\Plugin($lockBackend);
        $this->addPlugin($lockPlugin);
        
        // For things like AUTH
        foreach ( $this->_plugins as $serverPlugin ) {
            $this->addPlugin($serverPlugin);
        }

        // This ensures that we get a pretty index in the browser, but it is
        // optional.
        // $server->addPlugin(new \Sabre\DAV\Browser\Plugin());

        // All we need to do now, is to fire up the server
        $this->exec();
    }
}

