<?php

namespace Tygh\Tests\Unit\Core;

use Tygh\Less;

class LessTest extends \Tygh\Tests\Unit\ATestCase
{
    public $runTestInSeparateProcess = true;
    public $backupGlobals = false;
    public $preserveGlobalState = false;

    protected function setUp()
    {
        define('AREA', 'A');
        define('BOOTSTRAP', true);
        define('TIME', time());

        $this->requireCore('functions/fn.common.php');
        $this->requireCore('functions/fn.fs.php');

        $this->requireMockFunction('fn_set_hook');
    }

    /**
     * @dataProvider getParseUrlsTestData
     */
    public function testParseUrls($from_path, $to_path, $input_css, $expected_css)
    {
        $this->assertEquals(strtr($expected_css, array('[TIME]' => TIME)), Less::parseUrls($input_css, $from_path, $to_path));
    }

    /**
     * @dataProvider getNormalizeFontFamiliesTestData
     */
    public function testNormalizeFontFamilies($css, $expected_css)
    {
        $this->assertEquals($expected_css, Less::normalizeFontFamilies($css));
    }

    public function getParseUrlsTestData()
    {
        return array(
            array(
                '/var/www/cscart.dev/var/cache/misc/statics/design/themes/responsive/css',
                '/var/www/cscart.dev/design/themes/responsive/media',
                'background: url("../media/images/picker_bg_outside.png") repeat-x 0 -50% scroll #000;',
                "background: url('../../../../../../../../design/themes/responsive/media/images/picker_bg_outside.png?[TIME]') repeat-x 0 -50% scroll #000;"
            ),
            array(
                '/var/www/cscart.dev/var/cache/misc/statics/design/themes/responsive/css',
                '/var/www/cscart.dev/design/themes/responsive/media',
                "src:url('../media/fonts/glyphs.eot?#iefix') format('embedded-opentype')",
                "src:url('../../../../../../../../design/themes/responsive/media/fonts/glyphs.eot?[TIME]#iefix') format('embedded-opentype')"
            ),
            array(
                '/var/www/cscart.dev/var/cache/misc/statics/design/themes/responsive/css',
                '/var/www/cscart.dev/design/themes/responsive/media',
                "src:url('http://yandex.ru/image.png?#')",
                "src:url('http://yandex.ru/image.png?#')",
            ),
            array(
                '/var/www/cscart.dev/var/cache/misc/statics/design/themes/responsive/css',
                '/var/www/cscart.dev/design/themes/responsive/media',
                "src:url('picker.png')",
                "src:url('../../../../../../../../design/themes/responsive/media/picker.png?[TIME]')",
            ),
            array(
                '/var/www/cscart.dev/var/cache/misc/statics/design/themes/responsive/css',
                '/var/www/cscart.dev/design/themes/responsive/media',
                "src:url('../customer_screenshot.png')",
                "src:url('../../../../../../../../design/themes/responsive/media/../customer_screenshot.png?[TIME]')",
            ),
            array(
                '/var/www/cscart.dev/var/cache/misc/statics/design/themes/responsive/css',
                '/var/www/cscart.dev/design/themes/responsive/media',
                'background: url("data:image/gif;base64,R0lGODlhEAAQAMQAAORHHOVSKudfOulrSOp3WOyDZu6QdvCchPGolfO0o/XBs/fNwfjZ0frl3/zy7////wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAkAABAALAAAAAAQABAAAAVVICSOZGlCQAosJ6mu7fiyZeKqNKToQGDsM8hBADgUXoGAiqhSvp5QAnQKGIgUhwFUYLCVDFCrKUE1lBavAViFIDlTImbKC5Gm2hB0SlBCBMQiB0UjIQA7");',
                'background: url("data:image/gif;base64,R0lGODlhEAAQAMQAAORHHOVSKudfOulrSOp3WOyDZu6QdvCchPGolfO0o/XBs/fNwfjZ0frl3/zy7////wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAkAABAALAAAAAAQABAAAAVVICSOZGlCQAosJ6mu7fiyZeKqNKToQGDsM8hBADgUXoGAiqhSvp5QAnQKGIgUhwFUYLCVDFCrKUE1lBavAViFIDlTImbKC5Gm2hB0SlBCBMQiB0UjIQA7");',
            ),
            array(
                '/var/www/cscart.dev/var/cache/misc/statics/design/themes/responsive/css',
                '/var/www/cscart.dev/design/themes/responsive/media',
                "background: url('data:image/gif;base64,R0lGODlhEAAQAMQAAORHHOVSKudfOulrSOp3WOyDZu6QdvCchPGolfO0o/XBs/fNwfjZ0frl3/zy7////wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAkAABAALAAAAAAQABAAAAVVICSOZGlCQAosJ6mu7fiyZeKqNKToQGDsM8hBADgUXoGAiqhSvp5QAnQKGIgUhwFUYLCVDFCrKUE1lBavAViFIDlTImbKC5Gm2hB0SlBCBMQiB0UjIQA7');",
                "background: url('data:image/gif;base64,R0lGODlhEAAQAMQAAORHHOVSKudfOulrSOp3WOyDZu6QdvCchPGolfO0o/XBs/fNwfjZ0frl3/zy7////wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAkAABAALAAAAAAQABAAAAVVICSOZGlCQAosJ6mu7fiyZeKqNKToQGDsM8hBADgUXoGAiqhSvp5QAnQKGIgUhwFUYLCVDFCrKUE1lBavAViFIDlTImbKC5Gm2hB0SlBCBMQiB0UjIQA7');",
            ),
        );
    }

    public function getNormalizeFontFamiliesTestData()
    {
        return array(
            array(
                "font-family:inherit;",
                "font-family:inherit;"
            ),
            array(
                "font-family: inherit;",
                "font-family:inherit;"
            ),
            array(
                "font-family:'Sans';",
                "font-family:Sans;"
            ),
            array(
                "font-family: 'Sans';",
                "font-family:Sans;"
            ),
            array(
                "font-family:Open Sans;",
                "font-family:'Open Sans';"
            ),
            array(
                "font-family: Open Sans;",
                "font-family:'Open Sans';"
            ),
            array(
                "font-family:'Open Sans';",
                "font-family:'Open Sans';",
            ),
            array(
                "font-family: 'Open Sans';",
                "font-family:'Open Sans';"
            ),
            array(
                "font-family:Sans,Open Sans,serif;",
                "font-family:Sans,'Open Sans',serif;",
            ),
            array(
                "font-family: Sans, Open Sans, serif;",
                "font-family:Sans,'Open Sans',serif;",
            ),
            array(
                "font-family:'Sans','Open Sans','serif';",
                "font-family:Sans,'Open Sans',serif;",
            ),
            array(
                "font-family: 'Sans', 'Open Sans', 'serif';",
                "font-family:Sans,'Open Sans',serif;",
            ),
            array(
                "font-family:'Sans','Open Sans','serif';display:block;font-family:'Sans','PT Sans','serif';",
                "font-family:Sans,'Open Sans',serif;display:block;font-family:Sans,'PT Sans',serif;",
            ),
            array(
                "font-family: 'Sans','Open Sans','serif'; fake-property: Open Sans; font-family:'Sans','PT Sans','serif';",
                "font-family:Sans,'Open Sans',serif; fake-property: Open Sans; font-family:Sans,'PT Sans',serif;",
            ),
            array(
                "font-family: Sans !important;",
                "font-family:Sans !important;",
            ),
            array(
                "font-family: Open Sans !important;",
                "font-family:'Open Sans' !important;",
            ),
            array(
                "font-family: Sans, Open Sans !important;",
                "font-family:Sans,'Open Sans' !important;",
            ),
        );
    }
}
