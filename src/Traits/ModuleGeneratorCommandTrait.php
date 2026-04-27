<?php

namespace Teksite\Module\Traits;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;

trait ModuleGeneratorCommandTrait
{
    public function replaceStub(string $stub, array $replace, string $destination): void
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
            $this->error("Error writing to file: " . $e->getMessage());
        }

    }

    protected function getStubFile($path): string
    {
        return app('modules.stubs') . trim($path, '\/');
    }

    private function getStubContent(string $stubPath, array $replacements = []): string
    {
        if (!File::exists($stubPath)) {
            $this->error("$stubPath is not exists!");
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


    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the '.strtolower($this->type)],
        ];
    }

}
