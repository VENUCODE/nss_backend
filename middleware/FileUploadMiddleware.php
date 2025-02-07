<?php

function UploadMiddleware( $uploadDir )
 {
    return function ( $request, $handler ) use ( $uploadDir ) {
        $uploadedFiles = $request->getUploadedFiles();
        $fileNames = [];

        if ( !is_dir( $uploadDir ) ) {
            mkdir( $uploadDir, 0777, true );
        }

        foreach ( $uploadedFiles as $fileKey => $file ) {
            if ( is_array( $file ) ) {
                foreach ( $file as $singleFile ) {
                    if ( $singleFile->getError() === 0 ) {
                        // Generate filename once
                        $filename = bin2hex( random_bytes( 12 ) );
                        // Move file to the directory with the generated filename
                        $singleFile->moveTo( $uploadDir . '/' . $filename );
                        // Store the same filename in the fileNames array
                        $fileNames[ $fileKey ][] = $filename;
                    }
                }
            } else {
                if ( $file->getError() === 0 ) {

                    $filename = bin2hex( random_bytes( 12 ) );

                    $file->moveTo( $uploadDir . '/' . $filename );

                    $fileNames[ $fileKey ] = $filename;
                }
            }
        }

        $parsedBody = $request->getParsedBody();
        $request = $request->withAttribute( 'fileNames', $fileNames )
        ->withAttribute( 'parsedBody', $parsedBody );

        return $handler->handle( $request );
    }
    ;
}
