<?php

namespace RCS\Git;

class Server {
    public static function webdav($arguments)
    {
        // http://sabre.io/dav/gettingstarted/
        // Now we're creating a whole bunch of objects
        $rootDirectory = new \Sabre\DAV\FS\Directory(REPOSITORY_PATH . DIRECTORY_SEPARATOR . "{$arguments["repository"]}.git/");

        // The server object is responsible for making sense out of the WebDAV protocol
        $server = new \Sabre\DAV\Server($rootDirectory);

        // If your server is not on your webroot, make sure the following line has the
        // correct information
        $server->setBaseUri("/{$arguments["repository"]}.git");

        // The lock manager is reponsible for making sure users don't overwrite
        // each others changes.
        $lockBackend = new \Sabre\DAV\Locks\Backend\File(REPOSITORY_PATH . DIRECTORY_SEPARATOR . "{$arguments["repository"]}.git/locks");
        $lockPlugin = new \Sabre\DAV\Locks\Plugin($lockBackend);
        $server->addPlugin($lockPlugin);

        // This ensures that we get a pretty index in the browser, but it is
        // optional.
        $server->addPlugin(new \Sabre\DAV\Browser\Plugin());

        // All we need to do now, is to fire up the server
        $server->exec();
    }
}

