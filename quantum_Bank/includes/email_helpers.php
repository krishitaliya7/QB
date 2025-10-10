<?php
function render_email_template($templatePath, $vars = []) {
    extract($vars, EXTR_SKIP);
    ob_start();
    include $templatePath;
    return ob_get_clean();
}
