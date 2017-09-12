<?php

/*
 * This file is part of the OverblogThriftBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\ThriftBundle\CacheWarmer;

use Overblog\ThriftBundle\Compiler\ThriftCompiler;
use Overblog\ThriftBundle\Exception\CompilerException;
use Symfony\Component\ClassLoader\ClassMapGenerator;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Generate Thrift model in cache warmer.
 *
 * @author Xavier HAUSHERR
 */
class ThriftCompileCacheWarmer
{
    private $rootDir;
    private $cacheDir;
    private $path;
    private $services;

    /**
     * Cache Suffix for thrift compiled files.
     */
    const CACHE_SUFFIX = 'thrift';

    /**
     * Register dependencies.
     *
     * @param string $cacheDir
     * @param string $rootDir
     * @param array  $path
     * @param array  $services
     */
    public function __construct($cacheDir, $rootDir, $path, array $services)
    {
        $this->cacheDir = $cacheDir;
        $this->rootDir = $rootDir;
        $this->path = $path;
        $this->services = $services;
    }

    /**
     * Return definition path.
     *
     * @param string $definition
     * @param string $definitionPath
     *
     * @throws \Exception
     *
     * @return string
     */
    public function getDefinitionPath($definition, $definitionPath)
    {
        return sprintf('%s/../%s/%s.thrift',
                $this->rootDir,
                $definitionPath,
                $definition
            );
    }

    /**
     * Compile Thrift Model.
     *
     * @param bool $generateClassMap
     */
    public function compile($generateClassMap = true)
    {
        $compiler = new ThriftCompiler();
        $compiler->setExecPath($this->path);
        $cacheDir = sprintf('%s/%s', $this->cacheDir, self::CACHE_SUFFIX);

        // We compile for every Service
        foreach ($this->services as $config) {
            $definitionPath  = $this->getDefinitionPath(
                $config['definition'],
                $config['definitionPath']
            );

            //Set Path
            $compiler->setModelPath($cacheDir);

            //Set include dirs
            $compiler->setIncludeDirs($config['includeDirs']);

            //Add validate
            if ($config['validate']) {
                $compiler->addValidate();
            }

            $compile = $compiler->compile($definitionPath, $config['server']);

            // Compilation Error
            if (false === $compile) {
                throw new \RuntimeException(
                        sprintf('Unable to compile Thrift definition %s.', $definitionPath),
                        0,
                        new CompilerException(implode("\n", $compiler->getLastOutput()))
                    );
            }
        }

        if ($generateClassMap) {
            // Check if thrift cache directory exists
            $fs = new Filesystem();

            if (!$fs->exists($cacheDir)) {
                $fs->mkdir($cacheDir);
            }

            // Generate ClassMap
            ClassMapGenerator::dump($cacheDir, sprintf('%s/classes.map', $cacheDir));
        }
    }
}
