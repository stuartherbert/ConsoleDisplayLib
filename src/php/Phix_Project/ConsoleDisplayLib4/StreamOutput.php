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
 *   * Neither the names of the copyright holders nor the names of the
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

class StreamOutput implements ConsoleOutputEngine
{
        public $target = null;

        protected $forceTty = false;

        public function __construct($target)
        {
                $this->target = $target;
        }

        public function getColumnsHint()
        {
                $defaultHint = 78;

                if (!$this->isatty())
                {
                        return $defaultHint;
                }

                // is the ncurses extension installed?
                $hint = $this->getScreenWidthFromNcurses();
                if (\is_numeric($hint))
                {
                        // @codeCoverageIgnoreStart
                        //
                        // we always leave a small margin on the right
                        // hand side, to make sure that the text is easy
                        // to read ... just like the man command does
                        if ($hint > 2)
                        {
                                $hint -=2;
                        }
                        return $hint;
                        // @codeCoverageIgnoreEnd
                }

                // is the shell giving us a hint?
                $hint = \getenv('COLUMNS');
                if (\is_numeric($hint))
                {
                        // we always leave a small margin on the right
                        // hand side, to make sure that the text is easy
                        // to read ... just like the man command does
                        if ($hint > 2)
                        {
                                $hint -=2;
                        }
                        return $hint;
                }

                // nope, we'll just have to fall back on the default!
                return $defaultHint;
        }

        protected function getScreenWidthFromNcurses()
        {
                // @codeCoverageIgnoreStart
                if (!function_exists('ncurses_getmaxyx'))
                {
                        return false;
                }
                // @codeCoverageIgnoreEnd

                $screenWidth  = 0;
                $screenHeight = 0;

                if (!$this->isReallyATty())
                {
                        return false;
                }

                // @codeCoverageIgnoreStart
                ncurses_init();
                $fullscreen = ncurses_newwin ( 0, 0, 0, 0);
                ncurses_wrefresh($fullscreen);
                ncurses_getmaxyx ($fullscreen, $screenHeight, $screenWidth);
                ncurses_end();

                return $screenWidth;
                // @codeCoverageIgnoreEnd
        }

        public function writePartialLine($stringToOutput)
        {
                $fp = \fopen($this->target, 'a+');
                \fwrite($fp, $stringToOutput);
                \fclose($fp);
        }

        public function writeEmptyLines($eolsToWrite = 1)
        {
                $stringToOutput = '';
                for ($i = 0; $i < $eolsToWrite; $i++)
                {
                        $stringToOutput .= \PHP_EOL;
                }

                $this->writePartialLine($stringToOutput);
        }

        /**
         * Returns TRUE if our target is a file handle that writes to a
         * real terminal.
         *
         * Returns FALSE is our target is a file handle that writes to a
         * normal file, or a pipe (for example, the output of this program
         * is being piped into 'less').
         *
         * This is a separate method to assist with the testability of
         * this class.
         *
         * @return boolean
         */
        public function supportsColors()
        {
                return $this->isatty();
        }

        public function forceTty()
        {
                $this->forceTty = true;
        }

        protected function isatty()
        {
                static $isTty = null;

                // yuck ... for unit testing purposes
                if ($this->forceTty)
                {
                        return true;
                }

                if ($isTty === null)
                {
                        $isTty = $this->isReallyATty();
                }

                return $isTty;
        }

        protected function isReallyATty()
        {
                static $isTty = null;

                if ($isTty === null)
                {
                        $fp = \fopen($this->target, 'a+');
                        $isTty = posix_isatty($fp);
                        fclose($fp);
                }

                return $isTty;
        }
}

// if the POSIX extension is missing for any reason, this will save the day

if (!function_exists('posix_isatty'))
{
        function posix_isatty($dummy)
        {
                return false;
        }
}
?>
