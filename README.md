# laravel-php-latex

LaTex is an extraordinary typesetting system, using which you can highly professional and clean documentation. The document can be a simple article or huge technical/scientific book.

The reason to choose latex is that, it has extensive features inbuilt have headers, footers, book index, page numbers, watermarks and so on... Once you explore the possibilities of a latex document, you would be amazed.

This package makes entire scaffolding using which you can generate, save or download PDF documents.

- Pre-requisites : 

You need to have `texlive-full` program installed on your server. This program has tex packages and language libraries which help you generate documents.

- Usage :

1. Install the package using : 

~~~bash
composer require techsemicolon/laravel-php-latex
~~~

2. Create a view file with tex data : 

Create a view files inside `resources/views/lates/tex.blade.php`

~~~tex
\documentclass[a4paper,9pt,landscape]{article}

\usepackage{makecell}
\usepackage{adjustbox}
\usepackage[english]{babel}
\usepackage[scaled=.92]{helvet}
\usepackage{fancyhdr}
\usepackage{enumerate}
\usepackage[svgnames,table]{xcolor}
\usepackage[a4paper,inner=1.5cm,outer=1.5cm,top=1cm,bottom=1cm,bindingoffset=0cm]{geometry}
\usepackage{graphicx}
\usepackage{blindtext}
% \usepackage{draftwatermark}
\geometry{textwidth=\paperwidth, textheight=\paperheight, noheadfoot, nomarginpar}

\renewcommand{\familydefault}{\sfdefault}

\SetWatermarkText{Confidential}
\SetWatermarkScale{0.3}
\SetWatermarkColor[gray]{0.9}
\SetWatermarkAngle{0}

\pagestyle{fancy}
\fancyhead{} % clear all header fields
\renewcommand{\headrulewidth}{0pt} % no line in header area
\fancyfoot{} % clear all footer fields
\fancyfoot[LE,RO]{\thepage}           % page number in "outer" position of footer line

\fancyfoot[C]{\fontsize{8pt}{8pt}\selectfont Above document is auto-generated.}
\renewcommand{\footrulewidth}{0.2pt}


\begin{document}

\section*{\centering{Test Document}}

\begin{center}
	\item[Name :] {{ $name }}
	\item[Date of Birth :] {{ $dob }}
\end{center}

\blindtext

\begin{table}[ht]
\centering
\begin{adjustbox}{center}
\renewcommand{\arraystretch}{2}
\begin{tabular}{|l|l|}

\hline

\rowcolor[HTML]{E3E3E3}
\textbf{Sr. No} 
& \textbf{Addresses}\\
\hline

@foreach($addresses as $key => $address)
	\renewcommand{\arraystretch}{1.5}
	{{ $key }} & {{ $$address }} \\
	\hline
@endfoeach

\end{tabular}
\end{adjustbox}
\caption{Claim Summmary}
\end{table}

\blindtext

\vfill
\centering

\end{document}
~~~

You can see how we have easily used blade directives to dynamically generate the content.

3. Generate the file : 

There are 3 actions you can take to generate. 

1. You can download the file as a response :

~~~php
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Techsemicolon\Latex;

class TextController extends Controller
{
    /**
     * Download PDF generated from latex
     * 
     * @return Illuminate\Http\Response
     */
    public function download(){

        return (new Latex('latex.tex'))->with([
            'name' => 'John Doe',
            'dob' => '01/01/1994',
            'addresses' => [
                '20 Pumpkin Hill Drive Satellite Beach, FL 32937',
                '7408 South San Juan Ave. Beaver Falls, PA 15010'
            ]
        ])->download();
    }
}
~~~

2. You can save pdf to the location you want for later use :

~~~php
(new Latex('latex.tex'))->with([
    'name' => 'John Doe',
    'dob' => '01/01/1994',
    'addresses' => [
        '20 Pumpkin Hill Drive Satellite Beach, FL 32937',
        '7408 South San Juan Ave. Beaver Falls, PA 15010'
    ]
])->savePdf(storage_path('exports/pdf/test.pdf'));
~~~

3. You can just render the tex without generating :

~~~php
$tex = new Latex('latex.tex'))->with([
    'name' => 'John Doe',
    'dob' => '01/01/1994',
    'addresses' => [
        '20 Pumpkin Hill Drive Satellite Beach, FL 32937',
        '7408 South San Juan Ave. Beaver Falls, PA 15010'
    ]
])->render();
~~~



