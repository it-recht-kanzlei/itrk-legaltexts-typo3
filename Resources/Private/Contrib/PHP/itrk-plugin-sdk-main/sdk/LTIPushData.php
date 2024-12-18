<?php
/*
 * Please do NOT edit this class to ensure that the code remains executable.
 */

namespace ITRechtKanzlei;

class LTIPushData {
    public const DOCTYPE_IMPRINT              = 'impressum';
    public const DOCTYPE_TERMS_AND_CONDITIONS = 'agb';
    /** @deprecated */
    public const DOCTYPE_CAMCELLATION_POLICY  = 'widerruf';
    public const DOCTYPE_CANCELLATION_POLICY  = 'widerruf';
    public const DOCTYPE_PRIVACY_POLICY       = 'datenschutz';

    public const ALLOWED_DOCUMENT_TYPES = [
        self::DOCTYPE_IMPRINT,
        self::DOCTYPE_TERMS_AND_CONDITIONS,
        self::DOCTYPE_CANCELLATION_POLICY,
        self::DOCTYPE_PRIVACY_POLICY
    ];

    public const DOCTYPES_TO_MAIL = [
        self::DOCTYPE_TERMS_AND_CONDITIONS,
        self::DOCTYPE_CANCELLATION_POLICY
    ];

    protected $xmlData = null;

     /**
      * @throws LTIError
      */
    public function __construct(\SimpleXMLElement $xmlData) {
        $this->xmlData = $xmlData;

        $this->checkXmlData();
    }

    public function getMultiShopId(): string {
        // Only check this element, if it is explicitly requested.
        // The implementations that are not multishop-capable do not require
        // this parameter to be set.
        $this->checkXmlElementAvailable('user_account_id', null, LTIError::INVALID_USER_ACCOUNT_ID);
        return (string)$this->xmlData->user_account_id;
    }

    public function getTitle(): string {
        return (string)$this->xmlData->rechtstext_title;
    }

    public function getTextHtml(): string {
        return (string)$this->xmlData->rechtstext_html;
    }

    public function getText(): string {
        return (string)$this->xmlData->rechtstext_text;
    }

    public function getLanguageIso639_1(): string {
        return (string)$this->xmlData->rechtstext_language;
    }

    public function getLanguageIso639_2b(): string {
        return (string)$this->xmlData->rechtstext_language_iso639_2b;
    }

    public function getType(): string {
        return (string)$this->xmlData->rechtstext_type;
    }

    public function getCountry(): string {
        return (string)$this->xmlData->rechtstext_country;
    }

    public function getLocale(): string {
        return $this->getLanguageIso639_1().'_'.$this->getCountry();
    }

    public function getFileName(): string {
        return (string)$this->xmlData->rechtstext_pdf_filenamebase_suggestion;
    }

    public function getLocalizedFileName(): string {
        return (string)$this->xmlData->rechtstext_pdf_localized_filenamebase_suggestion;
    }

    public function hasPdf(): bool {
        return (($this->xmlData->rechtstext_pdf != null) && !empty($this->xmlData->rechtstext_pdf))
            || (($this->xmlData->rechtstext_pdf_url != null) && !empty($this->xmlData->rechtstext_pdf_url));
    }

    private function downloadPdf(): string {
        $this->checkXmlElementAvailable('rechtstext_pdf_url', null, LTIError::INVALID_DOCUMENT_PDF);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, (string)$this->xmlData->rechtstext_pdf_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $result = curl_exec($ch);
        $error = null;
        if (($errNo = curl_errno($ch)) !== CURLE_OK) {
            $error = new LTIError(
                sprintf('Unable to download PDF file. cURL error (%d): %s', $errNo, curl_error($ch)),
                LTIError::INVALID_DOCUMENT_PDF
            );
        } elseif (($statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE)) !== 200) {
            $error = new LTIError(
                sprintf('Unable to download PDF file. HTTP status code: %d.', $statusCode),
                LTIError::INVALID_DOCUMENT_PDF
            );
        }
        curl_close($ch);

        if ($error instanceof LTIError) {
            throw $error;
        }
        return $result;
    }

     /**
      * @throws LTIError
      */
    public function getPdf(): string {
        if (!$this->hasPdf()) {
            throw new LTIError('No pdf available!', LTIError::INVALID_DOCUMENT_PDF);
        }

        if (isset($this->xmlData->rechtstext_pdf) && !empty($this->xmlData->rechtstext_pdf)) {
            $pdfBin = base64_decode($this->xmlData->rechtstext_pdf, true);
        } else {
            $pdfBin = $this->downloadPdf();
        }

        if (substr($pdfBin, 0, 4) != '%PDF') {
            throw new LTIError('Content of PDF file is invalid.', LTIError::INVALID_DOCUMENT_PDF);
        }

        return (string)$pdfBin;
    }

    public function getApiVersion(): string {
        return (string)$this->xmlData->api_version;
    }

    /**
     * Returns the raw pared XML-Data. The use of this method is discuraged,
     * as each property you want to use is accessible via a dedicated getter.
     * This method is only available to access non-standard properties.
     * @return \SimpleXMLElement
     */
    public function getXml(): \SimpleXMLElement {
        return $this->xmlData;
    }

     /**
      * @throws LTIError
      */
    protected function checkXmlData() {
        $this->checkXmlElementAvailable('rechtstext_type', self::ALLOWED_DOCUMENT_TYPES, LTIError::INVALID_DOCUMENT_TYPE);
        if ((string)$this->xmlData->rechtstext_type !== 'impressum') {
            $this->checkXmlElementAvailable('rechtstext_pdf_filename_suggestion', null, LTIError::INVALID_FILE_NAME);
            $this->checkXmlElementAvailable('rechtstext_pdf_filenamebase_suggestion', null, LTIError::INVALID_FILE_NAME);
            $this->checkXmlElementAvailable('rechtstext_pdf_localized_filenamebase_suggestion', null, LTIError::INVALID_FILE_NAME);
        }
        $this->checkXmlElementAvailable('rechtstext_text', null, LTIError::INVALID_DOCUMENT_TEXT);
        $this->checkXmlElementAvailable('rechtstext_html', null, LTIError::INVALID_DOCUMENT_HTML);
        $this->checkXmlElementAvailable('rechtstext_title', null, LTIError::INVALID_DOCUMENT_TITLE);
        $this->checkXmlElementAvailable('rechtstext_country', null, LTIError::INVALID_DOCUMENT_COUNTRY);
        $this->checkXmlElementAvailable('rechtstext_language', null, LTIError::INVALID_DOCUMENT_LANGUAGE);
        $this->checkXmlElementAvailable('rechtstext_language_iso639_2b', null, LTIError::INVALID_DOCUMENT_LANGUAGE);
    }

    protected function checkXmlElementAvailable(string $name, ?array $allowedValues, int $errorCode): void {
        if (!isset($this->xmlData->$name)) {
            throw new LTIError('XML element ' . $name . ' not set!', $errorCode);
        }
        $value = (string)$this->xmlData->$name;
        if (empty($value)) {
            throw new LTIError('XML element ' . $name . '\'s value is empty!', $errorCode);
        }
        if (!empty($allowedValues) && !in_array($value, $allowedValues)) {
            throw new LTIError('Value of XML element ' . $name . ' is not as expected!', $errorCode);
        }
    }
}
