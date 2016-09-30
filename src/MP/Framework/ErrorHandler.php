<?php

namespace MP\Framework;

/**
 * Custom visual error handler inspired by PHP_Error
 */
class ErrorHandler
{
    const RENDER_MODE_HTML_BASIC    = 'basic';
    const RENDER_MODE_HTML_PRETTY   = 'pretty';
    const RENDER_MODE_JSON          = 'json';
    const RENDER_MODE_JSON_MESSAGES = 'json_messages';

    public $types_as_string = array(
        1 => 'E_ERROR',
        2 => 'E_WARNING',
        4 => 'E_PARSE',
        8 => 'E_NOTICE',
        16 => 'E_CORE_ERROR',
        32 => 'E_CORE_WARNING',
        64 => 'E_COMPILE_ERROR',
        128 => 'E_COMPILE_WARNING',
        256 => 'E_USER_ERROR',
        512 => 'E_USER_WARNING',
        1024 => 'E_USER_NOTICE',
        2048 => 'E_STRICT',
        4096 => 'E_RECOVERABLE_ERROR',
        8192 => 'E_DEPRECATED'
    );

    public $errors = array();
    public $render_mode = 'pretty';

    public function handler()
    {
        // "It is important to remember that the standard PHP error handler is
        // completely bypassed for the error types specified by error_types
        // unless the callback function returns FALSE"

        // "The following error types cannot be handled with a user defined
        // function: E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING,
        // E_COMPILE_ERROR, E_COMPILE_WARNING, and most of E_STRICT raised in
        // the file where set_error_handler() is called."

        // TODO: throw new ErrorException for backtrace?

        $args = func_get_args();

        $err = array(
            'type' => $args[0],
            'message' => $args[1],
            'file' => $args[2],
            'line' => $args[3],
            'context' => $args[4]
        );

        $this->errors[] = $err;
    }

    public function shutdown()
    {

        // Check for any final fatal errors
        $e = error_get_last();

        if (null !== $e) {
            $this->errors[] = $e;
        }

        if (count($this->errors) > 0) {
            header("HTTP/1.1 500 Internal Server Error");
            header("Content-type: text/html", true, 500);
            die($this->get_response_body());
        }
    }

    public function get_response_body()
    {
        return $this->render_all_errors($this->errors);
    }

    public function render_all_errors($errors)
    {
        if (static::RENDER_MODE_JSON_MESSAGES === $this->render_mode) {
            $messages = array();
            foreach ($errors as $error) {
                $messages[] = $error['type'] . ', '. $error['file'] . ":" . $error['line'] . " " . $error['message'];
            }

            return json_encode($messages);
        }

        if (static::RENDER_MODE_HTML_PRETTY === $this->render_mode) {
            $out = $this->render_errors_pretty($errors);
        } else {
            $out = '';
            foreach ($errors as $error) {
                $out .= $this->render_error($error);
            }
        }
        return $out;
    }

    public function render_error($err)
    {
        if ($this->render_mode == static::RENDER_MODE_HTML_BASIC) {
            return $this->render_error_basic($err);
        } elseif ($this->render_mode == static::RENDER_MODE_HTML_PRETTY) {
            return $this->render_error_pretty($err);
        } elseif ($this->render_mode == static::RENDER_MODE_JSON) {
            return $this->render_error_json($err);
        } else {
            return 'error';
        }
    }

    public function render_error_basic($err)
    {
        unset($err['context']); // it's too big
        return '<pre>' . print_r($err, true) . '</pre>';
    }

    public function render_error_json($error)
    {
        unset($err['context']); // it's too big
        return json_encode(array('error' => $error));
    }

    public function render_line($line)
    {
        return trim(str_replace(" ", '&nbsp;', $line));
    }

    public function render_error_pretty($error)
    {
        $file = file($error['file']);

        $pre_context_lines = 3;
        $post_context_lines = 3;

        $context = array_splice(
            $file,
            $error['line'] - $pre_context_lines,
            $pre_context_lines + $post_context_lines + 1
        );

        foreach ($context as $i => &$line) {
            $line_num = $error['line'] - $pre_context_lines + $i + 1;

            if ($line_num == $error['line']) {
                $line = '<span class="err_line hl">' . $line_num . ' ' . $this->render_line($line) . '</span></span>';
            } else {
                $line = '<span class="err_line mute">' . $line_num . '</span>  ' . $this->render_line($line);
            }
        }

        $frag = '<div><span class="type">%s</span><h2>%s</h2><span>%s  %s</span><br /><br />%s';

        $base = basename($error['file']);
        $disp_file = '<span class="mute">' .
                    str_replace($base, '<span class="file">' . $base . '</span>', $error['file'])
                    . '</span>';

        $out = sprintf(
            $frag,
            $this->types_as_string[$error['type']],
            $error['message'],
            $error['line'],
            $disp_file,
            implode("<br />\n", $context)
        );
        $out .= '</div>';

        return $out;
    }

    public function render_errors_pretty($errors)
    {
        $out = '';
        foreach ($errors as $error) {
            $out .= $this->render_error_pretty($error);
        }

        $body = <<<EOT
<style type="text/css">

.hl {
	color:#fe6;
	font-weight:bold;
	background-color:#112;
}

.php_error {
	margin:0px;
	padding:20px;
	padding-left:40px;
	font-family: monospace;
	color:#aaa;
	background-color:black;
	position:absolute;
	top:0px;
	left:0px;
	width:100%%;
	height:100%%;
	z-index:16777271;
}

.err_line {
	white-space: pre;
}

.mute {
	color:#aaa;
}

h2 {
	margin-top:10px;
	margin-bottom:10px;
	padding:0;

	color:#fe6;
}

.file {
	color:#9c0;
}

.php_error span.type {
	color:#cf0;
}
</style>

<div class="php_error" onclick="this.style.display = 'none';">
	%s
</div>
EOT;
        return sprintf($body, $out);
    }
}
