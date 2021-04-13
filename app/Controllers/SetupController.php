<?php namespace Controllers;

use Zephyrus\Utilities\ComposerPackage;
use Zephyrus\Application\Configuration;
use Zephyrus\Application\Session;
use Zephyrus\Network\Response;
use Zephyrus\Security\Cryptography;
use Zephyrus\Utilities\FileSystem\Directory;
use Zephyrus\Utilities\FileSystem\File;
use ZipArchive;

class SetupController extends Controller
{
    public function initializeRoutes()
    {
        $this->get("/", "index");
        $this->get("/setup", "setup");
        $this->get("/setup-cancel", "backward");
        $this->post("/setup", "forward");
    }

    public function index()
    {
        $setup = Session::getInstance()->read("setup", 0);
        if ($setup == 0) {
            Session::getInstance()->set("setup", 1);
            Session::getInstance()->set("setup_data", []);
        }
        return $this->render('setup/landing');
    }

    public function setup()
    {
        $data = Session::getInstance()->read("setup_data", []);
        $setup = Session::getInstance()->read("setup", 0);
        if ($setup == 0) {
            Session::getInstance()->set("setup", 1);
            Session::getInstance()->set("setup_data", []);
        }
        if ($setup < 7) {
            return $this->render('setup/start', [
                'data' => $data,
                'setup' => $setup,
                'examples' => (object) [
                    'currency' => $this->formatMoneyExample($data['application_locale'] ?? 'fr_CA', $data['application_currency'] ?? 'CAD'),
                    'timezone' => $this->formatDateTimeExample($data['application_timezone'] ?? 'America/New_York')
                ]
            ]);
        }
        $view = $this->render('setup/done', [
            'zephyrus_logo' => "data:image/png;base64,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",
        ]);
        $this->setupConfigIniFile();
        $this->setupFrontEnd();
        $this->setupOthers();
        $this->emptyProject();
        Session::getInstance()->destroy();
        return $view;
    }

    public function forward()
    {
        $form = $this->buildForm();
        Session::getInstance()->set("setup", Session::getInstance()->read("setup") + 1);
        Session::getInstance()->set("setup_data", array_merge(Session::getInstance()->read("setup_data"), $form->getFields()));
        return $this->redirect("/setup");
    }

    public function backward()
    {
        Session::getInstance()->set("setup", Session::getInstance()->read("setup") - 1);
        return $this->redirect("/setup");
    }

    /**
     * For this controller, all route rendering should include the latest Zephyrus core version in its parameters.
     *
     * @param string $page
     * @param array $args
     * @return Response
     */
    public function render($page, $args = []): Response
    {
        return parent::render($page, array_merge($args, [
            'system_date' => date(FORMAT_DATE_TIME),
            'zephyrus_version' => ComposerPackage::getVersion("zephyrus/zephyrus")
        ]));
    }

    private function setupConfigIniFile()
    {
        $data = Session::getInstance()->read("setup_data", []);
        $configurations = [
            'application' => [
                'env' => 'dev',
                'locale' => $data['application_locale'],
                'currency' => $data['application_currency'],
                'charset' => $data['application_charset'],
                'timezone' => $data['application_timezone']
            ],
            'database' => [],
            'session' => [
                'name' => Cryptography::randomString(20),
                'encryption_enabled' => ($data['security_session_encrypt'] == "1"),
                'fingerprint_ip' => ($data['security_session_hash'] == "1"),
                'fingerprint_agent' => ($data['security_session_hash'] == "1"),
                ';refresh_after_interval' => 60,
                ';refresh_after_requests' => 4
            ],
            'security' => [
                'ids_enabled' => ($data['security_ids'] == "1"),
                'csrf_guard_enabled' => ($data['security_csrf'] == "1"),
                'csrf_guard_automatic_html' => ($data['security_csrf'] == "1"),
                'encryption_algorithm' => 'aes-256-cbc',
                ';csrf_guard_methods' => ['POST', 'PUT', 'DELETE', 'PATCH'],
                ';ids_exceptions' => ['GET.__utmz', 'GET.__utmc']
            ],
            'lang' => [
                'date' => '%e %B %Y',
                'time' => '%H:%M',
                'datetime' => '%e %B %Y, %H:%M'
            ],
            'pug' => [
                'cache_enabled' => false,
                'cache_directory' => '/var/cache/pug'
            ]
        ];

        // Database settings
        $configurations['database']['dbms'] = $data['database_system'];
        if (!empty($data['database_host'])) {
            $configurations['database']['host'] = $data['database_host'];
        }
        if (!empty($data['database_port'])) {
            $configurations['database']['port'] = $data['database_port'];
        }
        if (!empty($data['database_name'])) {
            $configurations['database']['database'] = $data['database_name'];
        }
        if (!empty($data['database_username'])) {
            $configurations['database']['username'] = $data['database_username'];
        }
        if (!empty($data['database_password'])) {
            $configurations['database']['password'] = $data['database_password'];
        }
        if (!empty($data['database_charset'])) {
            $configurations['database']['charset'] = $data['database_charset'];
        }
        $configurations['database']['shared'] = ($data['database_shared'] == "1");
        $configurations['database']['convert_type'] = ($data['database_convert'] == "1");

        // Security session settings
        if ($data['security_session_refresh'] == "1") {
            $configurations['session']['refresh_probability'] = 0.4;
        } else {
            $configurations['session'][';refresh_probability'] = null;
        }
        $configurations['session'][';decoys'] = null;
        Configuration::getFile()->write($configurations);
        Configuration::getFile()->save();
    }

    private function setupFrontEnd()
    {
        $data = Session::getInstance()->read("setup_data", []);
        if ($data['frontend_framework'] == 'bootstrap_4.5.0') {
            $this->setupBootstrap();
        }
        if ($data['frontend_framework'] == 'bulma') {
            $this->setupBulma();
        }
        if ($data['frontend_framework'] == 'materialize') {
            $this->setupMaterialize();
        }
        if ($data['frontend_jquery'] == '1') {
            $this->setupJquery();
        }
        if ($data['frontend_fontawesome'] == '1') {
            $this->setupFontAwesome();
        }
        if ($data['frontend_lineicons'] == '1') {
            $this->setupLineIcons();
        }
        if ($data['frontend_moments'] == '1') {
            $this->setupMomentsJs();
        }
        if ($data['frontend_numeral'] == '1') {
            $this->setupNumeral();
        }
    }

    private function setupOthers()
    {
        $data = Session::getInstance()->read("setup_data", []);
        if ($data['others_codeclimate'] != '1') {
            (new File(ROOT_DIR . '/.codeclimate.yml'))->remove();
        }
        if ($data['others_travis'] != '1') {
            (new File(ROOT_DIR . '/.travis.yml'))->remove();
        }
        if ($data['others_unittest'] != '1') {
            (new File(ROOT_DIR . '/phpunit.xml'))->remove();
            (new Directory(ROOT_DIR . '/tests'))->remove();
        }
        if ($data['others_styleci'] != '1') {
            (new File(ROOT_DIR . '/.styleci.yml'))->remove();
        }
        if (!empty($data['others_git'])) {
            shell_exec('git init');
            shell_exec('git remote add origin ' . $data['others_git']);
        }
    }

    private function emptyProject()
    {
        $data = Session::getInstance()->read("setup_data", []);
        (new File(ROOT_DIR . '/app/Controllers/SetupController.php'))->remove();
        (new Directory(ROOT_DIR . '/app/Views/setup'))->remove();
        (new Directory(ROOT_DIR . '/public/assets/setup_archives'))->remove();
        (new Directory(ROOT_DIR . '/public/assets/images'))->remove();
        (new Directory(ROOT_DIR . '/locale/cache'))->remove();
        (new Directory(ROOT_DIR . '/public/stylesheets/images'))->remove();
        (new File(ROOT_DIR . '/locale/fr_CA/setup.json'))->remove();
        (new File(ROOT_DIR . '/locale/fr_CA/landing.json'))->remove();
        (new File(ROOT_DIR . '/public/javascripts/vendor/highlight.pack.js'))->remove();
        (new File(ROOT_DIR . '/public/stylesheets/vendor/highlight-default.css'))->remove();
        (new File(ROOT_DIR . '/public/stylesheets/vendor/pretty-checkbox.min.css'))->remove();
        (new File(ROOT_DIR . '/public/javascripts/app.js'))->remove();
        (new File(ROOT_DIR . '/public/stylesheets/style.css'))->remove();
        (new File(ROOT_DIR . '/public/stylesheets/setup.css'))->remove();
        (new File(ROOT_DIR . '/public/stylesheets/vendor/LineIcons.min.css'))->remove();
        if ($data['frontend_framework'] != 'bootstrap_4.5.0') {
            (new File(ROOT_DIR . '/public/stylesheets/vendor/bootstrap.min.css'))->remove();
            (new File(ROOT_DIR . '/public/stylesheets/vendor/bootstrap.min.css.map'))->remove();
            (new File(ROOT_DIR . '/public/javascripts/vendor/bootstrap.min.js'))->remove();
            (new File(ROOT_DIR . '/public/javascripts/vendor/bootstrap.min.js.map'))->remove();
        }
        if ($data['frontend_jquery'] != '1') {
            (new File(ROOT_DIR . '/public/javascripts/vendor/jquery-3.5.1.min.js'))->remove();
        }
        if ($data['frontend_lineicons'] != '1') {
            (new File(ROOT_DIR . '/public/stylesheets/fonts/LineIcons.eot'))->remove();
            (new File(ROOT_DIR . '/public/stylesheets/fonts/LineIcons.svg'))->remove();
            (new File(ROOT_DIR . '/public/stylesheets/fonts/LineIcons.ttf'))->remove();
            (new File(ROOT_DIR . '/public/stylesheets/fonts/LineIcons.woff'))->remove();
        }

        Directory::create(ROOT_DIR . '/public/stylesheets/images');
        Directory::create(ROOT_DIR . '/public/assets/images');
        File::create(ROOT_DIR . '/public/assets/images/.keep');
        File::create(ROOT_DIR . '/public/stylesheets/fonts/.keep');
        File::create(ROOT_DIR . '/public/stylesheets/vendor/.keep');
        File::create(ROOT_DIR . '/public/stylesheets/images/.keep');
        File::create(ROOT_DIR . '/public/javascripts/vendor/.keep');
        File::create(ROOT_DIR . '/public/javascripts/app.js');
        File::create(ROOT_DIR . '/public/stylesheets/style.css');

        $content = str_replace('/sample', '/', file_get_contents(ROOT_DIR . '/app/Controllers/ExampleController.php'));
        (new File(ROOT_DIR . '/app/Controllers/ExampleController.php'))->write($content);
    }

    private function setupBootstrap(string $distribution = "bootstrap-4.5.0-dist")
    {
        $zip = new ZipArchive();
        $zip->open(ROOT_DIR . "/public/assets/setup_archives/$distribution.zip");
        $zip->extractTo(ROOT_DIR . "/public/stylesheets/vendor/", array("$distribution/css/bootstrap.min.css", "$distribution/css/bootstrap.min.css.map"));
        $zip->extractTo(ROOT_DIR . "/public/javascripts/vendor/", array("$distribution/js/bootstrap.min.js", "$distribution/js/bootstrap.min.js.map"));
        $zip->close();
        @rename(ROOT_DIR . "/public/stylesheets/vendor/$distribution/css/bootstrap.min.css", ROOT_DIR . "/public/stylesheets/vendor/bootstrap.min.css");
        @rename(ROOT_DIR . "/public/stylesheets/vendor/$distribution/css/bootstrap.min.css.map", ROOT_DIR . "/public/stylesheets/vendor/bootstrap.min.css.map");
        (new Directory(ROOT_DIR . "/public/stylesheets/vendor/$distribution"))->remove();
        @rename(ROOT_DIR . "/public/javascripts/vendor/$distribution/js/bootstrap.min.js", ROOT_DIR . "/public/javascripts/vendor/bootstrap.min.js");
        @rename(ROOT_DIR . "/public/javascripts/vendor/$distribution/js/bootstrap.min.js.map", ROOT_DIR . "/public/javascripts/vendor/bootstrap.min.js.map");
        (new Directory(ROOT_DIR . "/public/javascripts/vendor/$distribution"))->remove();
    }

    private function setupBulma(string $distribution = "bulma-0.9.0")
    {
        $zip = new ZipArchive();
        $zip->open(ROOT_DIR . "/public/assets/setup_archives/$distribution.zip");
        $zip->extractTo(ROOT_DIR . "/public/stylesheets/vendor/", array("$distribution/css/bulma.min.css"));
        $zip->close();
        @rename(ROOT_DIR . "/public/stylesheets/vendor/$distribution/css/bulma.min.css", ROOT_DIR . "/public/stylesheets/vendor/bulma.min.css");
        (new Directory(ROOT_DIR . "/public/stylesheets/vendor/$distribution"))->remove();
    }

    private function setupMaterialize(string $distribution = "materialize-1.0.0")
    {
        $zip = new ZipArchive();
        $zip->open(ROOT_DIR . "/public/assets/setup_archives/$distribution.zip");
        $zip->extractTo(ROOT_DIR . "/public/stylesheets/vendor/", array("materialize/css/materialize.min.css"));
        $zip->extractTo(ROOT_DIR . "/public/javascripts/vendor/", array("materialize/js/materialize.min.js"));
        @rename(ROOT_DIR . "/public/stylesheets/vendor/materialize/css/materialize.min.css", ROOT_DIR . "/public/stylesheets/vendor/materialize.min.css");
        @rename(ROOT_DIR . "/public/javascripts/vendor/materialize/js/materialize.min.js", ROOT_DIR . "/public/javascripts/vendor/materialize.min.js");
        (new Directory(ROOT_DIR . "/public/stylesheets/vendor/materialize"))->remove();
        (new Directory(ROOT_DIR . "/public/javascripts/vendor/materialize"))->remove();
    }

    private function setupJquery(string $distribution = "jquery-3.5.1")
    {
        $zip = new ZipArchive();
        $zip->open(ROOT_DIR . "/public/assets/setup_archives/$distribution.zip");
        $zip->extractTo(ROOT_DIR . "/public/javascripts/vendor/", ['jquery-3.5.1.min.js']);
    }

    private function setupFontAwesome(string $distribution = "fontawesome-free-5.13.0-web")
    {
        $zip = new ZipArchive();
        $zip->open(ROOT_DIR . "/public/assets/setup_archives/$distribution.zip");
        $zip->extractTo(ROOT_DIR . "/public/stylesheets/vendor/");
        $fontAwesomeContent = file_get_contents(ROOT_DIR . "/public/stylesheets/vendor/$distribution/css/all.min.css");
        $fontAwesomeContent = str_replace('webfonts', 'fonts', $fontAwesomeContent);
        (new File(ROOT_DIR . "/public/stylesheets/vendor/$distribution/css/all.min.css"))->write($fontAwesomeContent);
        @rename(ROOT_DIR . "/public/stylesheets/vendor/$distribution/css/all.min.css", ROOT_DIR . "/public/stylesheets/vendor/fontawesome.min.css");
        (new Directory(ROOT_DIR . "/public/stylesheets/vendor/$distribution/webfonts"))->copy(ROOT_DIR . "/public/stylesheets/fonts");
        (new Directory(ROOT_DIR . "/public/stylesheets/vendor/__MACOSX"))->remove();
        (new Directory(ROOT_DIR . "/public/stylesheets/vendor/$distribution"))->remove();
    }

    private function setupLineIcons(string $distribution = "LineIcons-Package-2")
    {
        $zip = new ZipArchive();
        $zip->open(ROOT_DIR . "/public/assets/setup_archives/$distribution.zip");
        $zip->extractTo(ROOT_DIR . "/public/stylesheets/vendor/");
        @rename(ROOT_DIR . "/public/stylesheets/vendor/$distribution/WebFont/font-css/LineIcons.css", ROOT_DIR . "/public/stylesheets/vendor/LineIcons.css");
        (new Directory(ROOT_DIR . "/public/stylesheets/vendor/$distribution/WebFont/fonts"))->copy(ROOT_DIR . "/public/stylesheets/fonts");
        (new Directory(ROOT_DIR . "/public/stylesheets/vendor/__MACOSX"))->remove();
        (new Directory(ROOT_DIR . "/public/stylesheets/vendor/$distribution"))->remove();
    }

    private function setupMomentsJs(string $distribution = "moment-with-locales")
    {
        $zip = new ZipArchive();
        $zip->open(ROOT_DIR . "/public/assets/setup_archives/$distribution.zip");
        $zip->extractTo(ROOT_DIR . "/public/javascripts/vendor/", ['moment-with-locales.min.js']);
    }

    private function setupNumeral(string $distribution = "numeral-2.0.6")
    {
        $zip = new ZipArchive();
        $zip->open(ROOT_DIR . "/public/assets/setup_archives/$distribution.zip");
        $zip->extractTo(ROOT_DIR . "/public/javascripts/vendor/");
        @rename(ROOT_DIR . "/public/javascripts/vendor/adamwdraper-Numeral-js-7de892f/min", ROOT_DIR . "/public/javascripts/vendor/numeral");
        (new Directory(ROOT_DIR . "/public/javascripts/vendor/__MACOSX"))->remove();
        (new Directory(ROOT_DIR . "/public/javascripts/vendor/adamwdraper-Numeral-js-7de892f"))->remove();
    }

    private function formatMoneyExample($locale, $currency)
    {
        $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, 2);
        $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, 2);
        $formatter->setAttribute(\NumberFormatter::ROUNDING_MODE, \NumberFormatter::ROUND_HALFUP);
        return $formatter->formatCurrency(999999.99, $currency);
    }

    private function formatDateTimeExample(string $timezone)
    {
        date_default_timezone_set($timezone);
        $dateTime = strftime(Configuration::getConfiguration('lang', 'datetime'), time());
        date_default_timezone_set(Configuration::getApplicationConfiguration('timezone'));
        return $dateTime;
    }
}
