<?php

/**
 * Copyright (c) 2011-present Stuart Herbert.
 * Copyright (c) 2010 Gradwell dot com Ltd.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of the copyright holders nor the names of the
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package     Phix_Project
 * @subpackage  ConsoleDisplayLib4
 * @author      Stuart Herbert <stuart@stuartherbert.com>
 * @copyright   2011-present Stuart Herbert. www.stuartherbert.com
 * @copyright   2010 Gradwell dot com Ltd. www.gradwell.com
 * @license     http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link        http://www.phix-project.org
 * @version     @@PACKAGE_VERSION@@
 */

namespace Phix_Project\ConsoleDisplayLib4;

class ConsoleDisplay
{
        protected $wrapAt = 78;
        protected $indent = 0;

        // state to track the current line
        protected $currentLineLength = 0;

        // output engine to use
        public $outputEngine = null;

        public function __construct(ConsoleOutputEngine $outputEngine)
        {
                $this->outputEngine = $outputEngine;
                $this->setWrapFromOutput();
        }

        public function style($codes)
        {
                if (\is_array($codes))
                {
                        return \sprintf(ConsoleColor::ESCAPE_SEQUENCE, \implode(';', $codes));
                }
                else
                {
                        return \sprintf(ConsoleColor::ESCAPE_SEQUENCE, $codes);
                }
        }

        public function resetStyle()
        {
                return \sprintf(ConsoleColor::ESCAPE_SEQUENCE, ConsoleColor::NONE);
        }

        public function output($colors, $string = null)
        {
                if ($string === null)
                {
                        $this->writePartialLine(null, $colors);
                }
                else
                {
                        $this->writePartialLine($colors, $string);
                }
        }

        public function outputLine($colors, $string = null)
        {
                if ($string === null)
                {
                        $this->writeFullLine(null, $colors);
                }
                else
                {
                        $this->writeFullLine($colors, $string);
                }
        }

        public function outputBlankLine()
        {
                $this->writeBlankLine();
        }

        public function setIndent($indent)
        {
                $this->indent = $indent;
        }

        public function addIndent($indent)
        {
                $this->indent += $indent;
        }

        public function getIndent()
        {
                return $this->indent;
        }

        public function getWrapAt()
        {
                return $this->wrapAt;
        }

        public function setWrapAt($wrapAt)
        {
                $this->wrapAt = $wrapAt;
        }

        public function setWrapFromOutput()
        {
                $this->wrapAt = $this->outputEngine->getColumnsHint();
        }

        protected function writePartialLine($colors, $string)
        {
                // create the string to output
                $stringToWrite = '';
                if ($this->outputEngine->supportsColors())
                {
                        $stringToWrite .= $this->style($colors);
                }
                $stringToWrite .= $this->createWrappedStrings($string);
                if ($this->outputEngine->supportsColors())
                {
                        $stringToWrite .= $this->resetStyle();
                }

                // output the string
                $this->outputEngine->writePartialLine($stringToWrite);
        }

        protected function writeFullLine($colors, $string)
        {
                // create the string to output
                $stringToWrite = '';
                if ($this->outputEngine->supportsColors())
                {
                        $stringToWrite .= $this->style($colors);
                }
                $stringToWrite .= $this->createWrappedStrings($string);
                if ($this->outputEngine->supportsColors())
                {
                        $stringToWrite .= $this->resetStyle();
                }
                $stringToWrite .= \PHP_EOL;

                // output the string
                $this->outputEngine->writePartialLine($stringToWrite);

                // reset the line length afterwards
                $this->currentLineLength = 0;
        }

        protected function writeBlankLine()
        {
                $eolsToWrite = 1;
                if ($this->currentLineLength !== 0)
                {
                        $eolsToWrite = 2;
                        $this->currentLineLength = 0;
                }
                $this->outputEngine->writeEmptyLines($eolsToWrite);
        }

        protected function createWrappedStrings($string)
        {
                // this is to ensure deterministic behaviour
                static $lastRtrim = '';

                // var_dump('createWrappedStrings called');
                // var_dump($lastRtrim);

                $return = '';
                $strings = \explode(PHP_EOL, $string);
                $append = false;

                foreach ($strings as $string)
                {
                        // general case
                        if ($append)
                        {
                                $return .= \PHP_EOL;
                                $this->currentLineLength = 0;

                                if (\strlen($string) > 0)
                                {
                                        $return .= $this->createWrappedString($string);
                                }
                        }
                        else
                        {
                                $append = true;
                                // var_dump('loop string: ' . $string);
                                // var_dump('strlen(trim): '  . strlen(trim($string)));

                                if (\strlen($string) > 0)
                                {
                                        $return = $this->createWrappedString($string);

                                        if (substr($return, 0, strlen(\PHP_EOL)) != \PHP_EOL)
                                        {
                                                // we need the whitespace adding
                                                $return = $lastRtrim . $return;
                                        }
                                }

                                $lastRtrim = '';
                        }

                }

                // is there whitespace we need to chop?
                $rtrimmedString = rtrim($return, ' ');
                $rtrimmedLen    = strlen($rtrimmedString);
                $returnLen      = strlen($return);

                // var_dump('string: ' . $string);
                // var_dump('return: ' . $return);
                // var_dump('rtrimmedString: ' . $rtrimmedString);
                // var_dump('rtrimmedLen: ' . $rtrimmedLen);
                // var_dump('returnLen: ' . $returnLen);

                if ($rtrimmedLen !== $returnLen)
                {
                        $lastRtrim = substr($return, $rtrimmedLen);
                        $return    = $rtrimmedString;
                }

                // var_dump('lastRtrim: ' . $lastRtrim);
                // var_dump('return: ' . $return);

                // all done
                return $return;
        }

        protected function createWrappedString($string)
        {
                $return = '';

                // what will we split the line on?
                $separators = array(' ' => true, '\\' => true, '/' => true);

                // which characters do we wish to skip when splitting
                // the line?
                $whitespace = array(' ' => true);

                while (\strlen($string) > 0)
                {
                        // step 1: are we at the beginning of the line?
                        $return .= $this->createLineIndent();

                        // step 2: do we need to split the line?
                        if (!$this->doesStringNeedWrapping($string))
                        {
                                // no; just output and go
                                $return .= $string;
                                $this->currentLineLength += \strlen($string);
                                $string = '';
                        }
                        else
                        {
                                // if we get here, the string needs wrapping (if possible)
                                $rawWrapPoint = $this->wrapAt - $this->currentLineLength;
                                $wrapPoint = $rawWrapPoint;
                                while ($wrapPoint > 0 && !isset($separators[$string{$wrapPoint}]))
                                {
                                        $wrapPoint--;
                                }

                                if ($wrapPoint == 0)
                                {
                                        // we will have to wrap in the middle of this
                                        // silly length string
                                        if (\strlen($string) > $this->wrapAt)
                                        {
                                                $wrapPoint = $rawWrapPoint;
                                        }
                                }

                                if ($wrapPoint > 0)
                                {
                                        $return .= \substr($string, 0, $wrapPoint) . \PHP_EOL;
                                        if (isset($whitespace[$string{$wrapPoint}]))
                                        {
                                                $wrapPoint++;
                                        }
                                        $string = \substr($string, $wrapPoint);
                                }
                                else
                                {
                                        $return .= PHP_EOL;
                                }
                                $this->currentLineLength = 0;
                        }
                }

                // all done
                return $return;
        }

        protected function createLineIndent()
        {
                $return = '';

                if ($this->currentLineLength < $this->indent)
                {
                        $indent = $this->indent - $this->currentLineLength;
                        // we need to write out the indent
                        $return .= \str_repeat(' ', $indent);
                        $this->currentLineLength += $indent;
                }

                return $return;
        }

        protected function doesStringNeedWrapping($string)
        {
                if ($this->currentLineLength + \strlen($string) <= $this->wrapAt)
                {
                        return false;
                }

                return true;
        }
}
