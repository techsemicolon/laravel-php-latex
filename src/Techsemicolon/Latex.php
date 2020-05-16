<?php

namespace Techsemicolon;

use Techsemicolon\LatextException;
use Techsemicolon\LatexPdfWasGenerated;
use Techsemicolon\LatexPdfFailed;
use Techsemicolon\ViewNotFoundException;
use Symfony\Component\Process\Process;

class Latex
{
	/**
	 * Stub view file path
	 * @var string
	 */
    private $stubPath;

    /**
     * Data to pass to the stub
     * 
     * @var array
     */
    private $data;

    /**
     * Rendered tex file
     * 
     * @var string
     */
    private $renderedTex;

    /**
     * If it's a raw tex or a view file
     * @var boolean
     */
    private $isRaw = false;

    /**
     * Metadata of the generated pdf
     * @var mixed
     */
    private $metadata;

    /**
     * Path of pdflatex
     * 
     * @var string
     */
    private $binPath;

    /**
     * File Name inside Zip
     * 
     * @var string
     */
    private $nameInsideZip;

    /**
     * Construct the instance
     * 
     * @param string $stubPath
     * @param mixed $metadata
     */
    public function __construct($stubPath = null, $metadata = null){

        if($stubPath instanceof RawTex){

            $this->isRaw = true;
            $this->renderedTex = $stubPath->getTex();
        }
        else{

           $this->stubPath = $stubPath;
        }
        
        $this->metadata = $metadata;

    }

    /**
     * Set the path of pdflatex
     * 
     * @param  string $binPath
     * 
     * @return Latex
     */
    public function binPath($binPath){

        if(is_string($binPath)){

            $this->binPath = $binPath;
        }

        return $this;
    }

    /**
     * Set name inside zip file
     * 
     * @param  string $nameInsideZip
     * 
     * @return Latex
     */
    public function setName($nameInsideZip){

        if(is_string($nameInsideZip)){

            $this->nameInsideZip = basename($nameInsideZip);
        }

        return $this;
    }

    /**
     * Get name inside zip file
     * 
     * @return string
     */
    public function getName(){

        return $this->nameInsideZip;
    }

    /**
     * Set the with data
     * 
     * @param  array $data
     * 
     * @return Latex
     */
    public function with($data){

    	$this->data = $data;

    	return $this;
    }

    /**
     * Dry run
     * 
     * @return Illuminate\Http\Response
     */
    public function dryRun(){

        $this->isRaw = true;

        $process = new Process(["which", "pdflatex"]);
        $process->run();

        if (!$process->isSuccessful()) {
            
            throw new LatextException($process->getOutput());
        }

        $this->renderedTex = \File::get(dirname(__FILE__).'/dryrun.tex');
        
        return $this->download('dryrun.pdf');
    }

    /**
     * Render the stub with data
     * 
     * @return string
     * @throws ViewNotFoundException
     */
    public function render(){

        if($this->renderedTex){
            
           return $this->renderedTex;
        }

        if(!view()->exists($this->stubPath)){
            
            throw new ViewNotFoundException('View ' . $this->stubPath . ' not found.');
        }

    	$this->renderedTex = view($this->stubPath, $this->data)->render();

    	return $this->renderedTex;
    }

    /**
     * Save generated PDF
     * 
     * @param  string $location
     * 
     * @return boolean
     */
    public function savePdf($location)
    {
        $this->render();

        $pdfPath = $this->generate();

        $fileMoved = \File::move($pdfPath, $location);

        \Event::dispatch(new LatexPdfWasGenerated($location, 'savepdf', $this->metadata));

        return $fileMoved;
    }

    /**
     * Download file as a response
     *
     * @param  string|null $fileName
     * @return Illuminate\Http\Response
     */
    public function download($fileName = null)
    {
        if(!$this->isRaw){
           $this->render();
        }

        $pdfPath = $this->generate();

        if(!$fileName){
            $fileName = basename($pdfPath);
        }

        \Event::dispatch(new LatexPdfWasGenerated($fileName, 'download', $this->metadata));

        return \Response::download($pdfPath, $fileName, [
              'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Generate the PDF
     * 
     * @return string
     */
    private function generate(){

    	$fileName = str_random(10);
        $tmpfname = tempnam(sys_get_temp_dir(), $fileName);
        $tmpDir = sys_get_temp_dir();
        chmod($tmpfname, 0755);

        \File::put($tmpfname, $this->renderedTex);

        $program    = $this->binPath ? $this->binPath : 'pdflatex';
        $cmd        = [$program, "-output-directory", $tmpDir, $tmpfname];
        
        $process    = new Process($cmd);
        $process->run();

        if (!$process->isSuccessful()) {
        	
            \Event::dispatch(new LatexPdfFailed($fileName, 'download', $this->metadata));
        	$this->parseError($tmpfname, $process);
        }

        $this->teardown($tmpfname);

        register_shutdown_function(function () use ($tmpfname) {

            if(\File::exists($tmpfname . '.pdf')){
                \File::delete($tmpfname . '.pdf');
            }
        });

        return $tmpfname.'.pdf';
    }

    /**
     * Teardown secondary files
     * 
     * @param  string $tmpfname
     * 
     * @return void
     */
    private function teardown($tmpfname)
    {
        if(\File::exists($tmpfname)){
            \File::delete($tmpfname);
        }
        if(\File::exists($tmpfname . '.aux')){
            \File::delete($tmpfname . '.aux');
        }
        if(\File::exists($tmpfname . '.log')){
            \File::delete($tmpfname . '.log');
        }

        return $this;
    }

    /**
     * Throw error from log gile
     * 
     * @param  string $tmpfname
     * 
     * @throws \LatextException
     */
    private function parseError($tmpfname, $process){

    	$logFile = $tmpfname.'.log';

    	if(!\File::exists($logFile)){

    		throw new LatextException($process->getOutput());
    	}

    	$error = \File::get($tmpfname.'.log');
    	throw new LatextException($error);
    }
}
