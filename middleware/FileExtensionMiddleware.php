<?php
function fileExtensionMiddleware($allowedExtensions){
    return function ($request, $handler) use ($allowedExtensions) {
        $uploadedFiles = $request->getUploadedFiles();
        if (empty($uploadedFiles)) {
            return $handler->handle($request);
        }

        $invalidExtensions = [];
        foreach ($uploadedFiles as $field => $file) {

            if (is_array($file)) {
                foreach ($file as $singleFile) {
                    $extension = strtolower(pathinfo($singleFile->getClientFilename(), PATHINFO_EXTENSION));
        
                    if (!in_array($extension, $allowedExtensions)) {
                        $invalidExtensions[] = "{$singleFile->getClientFilename()} (.$extension)";
                    }
                }
            } else {
                $extension = strtolower(pathinfo($file->getClientFilename(), PATHINFO_EXTENSION));
                if (!in_array($extension, $allowedExtensions)) {
                    $invalidExtensions[] = "{$file->getClientFilename()} (.$extension)";
                }
            }
        }
        if (!empty($invalidExtensions)) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode(["error"=>"Invalid File Extension","message"=>'Invalid file extensions: ' . implode(', ', $invalidExtensions)]));
            return $response->withStatus(400)->withHeader("Content-type","application/json");
        }
        return $handler->handle($request);
    };
}
