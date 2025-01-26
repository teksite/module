<?php

namespace Teksite\Module\Traits;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

trait ModuleGeneratorCommandTrait
{
    public function replaceStub(string $stub, array $replace, string $destination)
    {
        $stubPath = $this->getStubFile($stub);
        $replacedContent = $this->getStubContent($stubPath, $replace);
        if (!File::exists(dirname($destination))) {
            File::makeDirectory(dirname($destination), 0755, true);
        }

        // Write to the file
        try {
            File::put($destination, $replacedContent);
        } catch (\Exception $e) {
            dd("Error writing to file: " . $e->getMessage());
        }

    }

    protected function getStubFile($path)
    {
        return app('module.stubs') . trim($path, '\/');
    }

    private function getStubContent(string $stubPath, array $replacements = []): string
    {
        if (!File::exists($stubPath)) {
            $this->error('there is not ant stub at ' . $stubPath);
            return '';
        }

        $content = File::get($stubPath);

        if (count($replacements)) {
            foreach ($replacements as $key => $value) {
                $content = str_replace($key, $value, $content);
            }
        }
        return $content;
    }


}
