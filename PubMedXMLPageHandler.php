<?php

namespace APP\plugins\generic\pubMedXML;

use APP\core\Request;
use APP\facades\Repo;
use APP\handler\Handler;
use APP\search\ArticleSearch;
use APP\template\TemplateManager;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use PKP\controllers\page\PageHandler;
use PKP\security\authorization\ContextAccessPolicy;
use PKP\security\authorization\ContextRequiredPolicy;
use PKP\security\authorization\PKPSiteAccessPolicy;

class PubMedXMLPageHandler extends Handler {

    public PubMedXMLPlugin $plugin;

    public function __construct(PubMedXMLPlugin $plugin)
    {
        parent::__construct();

        $this->plugin = $plugin;
    }

    public function escape($in) {
        return htmlspecialchars($in, ENT_XML1, 'UTF-8');
    }

    public function tag($tagName, $val, $args = null) {
        if(is_array($val)) { var_dump($val); }
        return '<' . $tagName . ($args ? ( ' ' . $args ) : '') . '>' . $this->escape($val) . '</' . $tagName . '>';
    }

    public function viewXML($args, $request)
    {
    
        $article_id = $args[0];
        $article = Repo::submission()->get( $article_id );
        $site = $request->getSite();
        $journal = $request->getContext();
        $publication = $article->getCurrentPublication();
        $issue = Repo::issue()->get( $publication->getIssueId() );
        $published = new \DateTime( $publication->getData('datePublished') );
        
        $pages = $publication->getPageArray();
        $firstPage = $lastPage = '';
        if (!empty($pages)) {
            $firstRange = array_shift($pages);
            $firstPage = array_shift($firstRange);
            if (count($firstRange)) {
                // There is a first page and last page for the first range
                $lastPage = array_shift($firstRange);
            } else {
                // There is not a range in the first segment
                $lastPage = '';
            }
        }
        
        $out = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<!DOCTYPE ArticleSet PUBLIC "-//NLM//DTD PubMed 2.7//EN" "https://dtd.nlm.nih.gov/ncbi/pubmed/in/PubMed.dtd">',
            '<ArticleSet>',
            '<Article>',
            '<Journal>',
            $this->tag( 'PublisherName', $site->getTitle('en') ),
            $this->tag( 'JournalTitle', $journal->getName('en') ),
            $this->tag( 'Issn', $journal->getData('onlineIssn') ),
            $this->tag( 'Volume', $issue->getVolume() ),
            $this->tag( 'Issue', $issue->getNumber() ),
            '<PubDate PubStatus="epublish">',
            $this->tag('Year', $published->format('Y')),
            $this->tag('Month', $published->format('m')),
            $this->tag('Day', $published->format('d')),
            '</PubDate>',
            '</Journal>',
            
            $this->tag('ArticleTitle', $publication->getLocalizedTitle('en')),
            $this->tag('VernacularTitle', ''), // TBC?
            $this->tag('FirstPage', $firstPage),
            $this->tag('LastPage', $lastPage),
            $this->tag('ELocationID', $publication->getDoi(), 'EIdType="doi"'),
            $this->tag('Language', strtoupper($journal->getPrimaryLocale())),
            
            '<AuthorList>',
        ];

        foreach($publication->getData('authors') as $author) {
            $out[] = '<Author>';
            $out[] = $this->tag('FirstName', $author->getGivenName('en'));
            $out[] = $this->tag('LastName', $author->getFamilyName('en'));
            $aff = [];
            foreach($author->getAffiliations() as $affObject) {
                $aff[] = $affObject->getLocalizedName('en');
            }
            $out[] = $this->tag('Affiliation', implode("; ", $aff));
            $out[] = $this->tag('Identifier', $author->getData('orcid'), 'Source="ORCID"');
            $out[] = '</Author>';
        }

        $out[] = '</AuthorList>';
        
        $out[] = $this->tag('PublicationType', 'Journal Article');
        $out[] = implode("\n", [
            '<History>',
            '<PubDate PubStatus="received">',
            $this->tag('Year', $published->format('Y')),
            $this->tag('Month', $published->format('m')),
            $this->tag('Day', $published->format('d')),
            '</PubDate>',
            '</History>'
        ]);

        $out[] = $this->tag('Abstract', strip_tags($publication->getLocalizedData('abstract')));

        $categories = $publication->getData('keywords'); 
        if(!empty($categories)) {
            $out[] = '<ObjectList>';
            foreach($categories['en'] as $category) {
                $out[] = '<Object Type="keyword">';
                $out[] = $this->tag( 'Param', $category, 'Name="value"' );
                $out[] = '</Object>';
            }
            $out[] = '</ObjectList>';
        }

        $out[] = '</Article>';
        $out[] = '</ArticleSet>';

        if(@$_GET['format'] == 'text') {
            @header('Content-Type: text/plain');
        } else {
            @header('Content-Type: text/xml');
        }
        echo implode("\n", $out);
        exit;

    }

}