<?php

if (class_exists('Timber')) {
  $GLOBALS['timberContext'] = Timber::context();
  ob_start();
}
