<?php

/*
 * Copyright (c) 2018 François Kooman <fkooman@tuxed.net>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace fkooman\Tpl;

use fkooman\Tpl\Exception\TemplateException;

class Template
{
    /** @var array */
    private $templateDirList;

    /** @var null|string */
    private $translationFile;

    /** @var null|string */
    private $activeSectionName = null;

    /** @var array */
    private $sectionList = [];

    /** @var array */
    private $layoutList = [];

    /** @var array */
    private $templateVariables = [];

    /** @var array */
    private $callbackList = [];

    /**
     * @param array  $templateDirList
     * @param string $translationFile
     */
    public function __construct(array $templateDirList, $translationFile = null)
    {
        $this->templateDirList = $templateDirList;
        $this->translationFile = $translationFile;
    }

    /**
     * @param array $templateVariables
     *
     * @return void
     */
    public function addDefault(array $templateVariables)
    {
        $this->templateVariables = \array_merge($this->templateVariables, $templateVariables);
    }

    /**
     * @param string   $callbackName
     * @param callable $cb
     *
     * @return void
     */
    public function addCallback($callbackName, callable $cb)
    {
        $this->callbackList[$callbackName] = $cb;
    }

    /**
     * @param string $templateName
     * @param array  $templateVariables
     *
     * @return string
     */
    public function render($templateName, array $templateVariables = [])
    {
        // XXX see what gets added every time, to see if we don't overdo it!
        $this->templateVariables = \array_merge($this->templateVariables, $templateVariables);
        \extract($this->templateVariables);
        \ob_start();
        /** @psalm-suppress UnresolvableInclude */
        include $this->templatePath($templateName);
        $templateStr = \ob_get_clean();
        if (0 !== \count($this->layoutList)) {
            $templateName = \array_keys($this->layoutList)[0];
            $templateVariables = $this->layoutList[$templateName];
            // because we use render we must empty the layoutList
            $this->layoutList = [];

            return $this->render($templateName, $templateVariables);
        }

        return $templateStr;
    }

    /**
     * @param string $templateName
     * @param array  $templateVariables
     *
     * @return string
     */
    public function insert($templateName, array $templateVariables = [])
    {
        // XXX we have to do something with the layoutList?! Seems not!
        return $this->render($templateName, $templateVariables);
    }

    /**
     * @param string $sectionName
     *
     * @return void
     */
    public function start($sectionName)
    {
        if (null !== $this->activeSectionName) {
            throw new TemplateException(\sprintf('section "%s" already started', $this->activeSectionName));
        }

        $this->activeSectionName = $sectionName;
        \ob_start();
    }

    /**
     * @return void
     */
    public function stop()
    {
        if (null === $this->activeSectionName) {
            throw new TemplateException('no section started');
        }

        $this->sectionList[$this->activeSectionName] = \ob_get_clean();
        $this->activeSectionName = null;
    }

    /**
     * @param string $layoutName
     * @param array  $templateVariables
     *
     * @return void
     */
    public function layout($layoutName, array $templateVariables = [])
    {
        $this->layoutList[$layoutName] = $templateVariables;
    }

    /**
     * @param string $sectionName
     *
     * @return string
     */
    public function section($sectionName)
    {
        if (!\array_key_exists($sectionName, $this->sectionList)) {
            throw new TemplateException(\sprintf('section "%s" does not exist', $sectionName));
        }

        return $this->sectionList[$sectionName];
    }

    /**
     * @param string      $v
     * @param null|string $cb
     *
     * @return string
     */
    private function e($v, $cb = null)
    {
        if (null !== $cb) {
            $functionList = \explode('|', $cb);
            foreach ($functionList as $f) {
                if (!\function_exists($f)) {
                    if (!\array_key_exists($f, $this->callbackList)) {
                        throw new TemplateException(\sprintf('function "%s" does not exist', $f));
                    }
                    $f = $this->callbackList[$f];
                }
                $v = \call_user_func($f, $v);
            }
        }

        return \htmlentities($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * @param string $v
     *
     * @return string
     */
    private function t($v)
    {
        if (null === $this->translationFile) {
            // no translation file, use original
            $translatedText = $v;
        } else {
            /** @psalm-suppress UnresolvableInclude */
            $translationData = include $this->translationFile;
            if (\array_key_exists($v, $translationData)) {
                // translation found
                $translatedText = $translationData[$v];
            } else {
                // not found, use original
                $translatedText = $v;
            }
        }

        // find all string values, wrap the key, and escape the variable
        $escapedVars = [];
        foreach ($this->templateVariables as $k => $v) {
            if (\is_string($v)) {
                $escapedVars['%'.$k.'%'] = $this->e($v);
            }
        }

        return \str_replace(\array_keys($escapedVars), \array_values($escapedVars), $translatedText);
    }

    /**
     * @param string $templateName
     *
     * @return string
     */
    private function templatePath($templateName)
    {
        foreach ($this->templateDirList as $templateDir) {
            if (\file_exists($templateDir.'/'.$templateName)) {
                return $templateDir.'/'.$templateName;
            }
            if (\file_exists($templateDir.'/'.$templateName.'.php')) {
                return $templateDir.'/'.$templateName.'.php';
            }
        }

        throw new TemplateException(\sprintf('template "%s" does not exist', $templateName));
    }
}
