#Broken Links Finder
===

## Description:
A web application(tool) that allows web developers to generate a report consisting of all the broken links in their website. It helps in knowing which links are valid and which links are dead.

##Requirements:
1. PHP
2. FPDF Library(included).

##Installation:
Clone the Repository to your PHP htdocs/www folder.
	`$ git clone https://github.com/crusador/Broken-Links-Finder-PHP.git`


##Running:
Run the crawler.php file with the following GET parameters.
     url = <url to be crawled>
     email = <Email address to which the mail is to be sent> 
     depthlevel = <depthlevel of crawling>;
     type = <1 - PDF, 2 - HTML, 3 - CSV>;
     forma = <Just put the initials of each Status COde you want to be considered broken i.e 3 for 30X, '45' for 40X,50X and so on>;
Example 
     /crawler.php?url=codebreaker.co.in&email=msg.jitesh@gmail.com&forma=45&depthlevel=5&type=1

##Copyright and Licence
2012 [JAKWorks] (http://www.facebook.com/JAKWorks)

Broken Links Finder by JAKWorks is licensed under a [Creative Commons Attribution-NonCommercial-ShareAlike 3.0 Unported License](http://creativecommons.org/licenses/by-nc-sa/3.0/)
