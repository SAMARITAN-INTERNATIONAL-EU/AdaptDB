# Adapt DB - Generate Documentation


## Generate Documentation from Source Code

## Generate PHP Documentation

#### Installation

 - **phpDocumenator**
	To generate the PHPDocs it is required that `phpDocumentor.phar` exists in the projects-root folder. Please check out the [homepage of phpDocumentor](https://www.phpdoc.org/) for more information.
 - **(optional) Install GraphViz-Tool to generate a graph-visualization**
	PHPDoc will try to generate graphs of the project using the GraphViz-Tool. This can be downloaded from their website: [http://www.graphviz.org/](http://www.graphviz.org/).

### Generate PHPDoc

Execute `phing generateDocumentation` from the command line.

After the tool is installed you need to add the bin-folder of the application to the PATH-variable. On Windows systems the default path is similar to this: `C:\Program Files (x86)\Graphviz2.38\bin`.


## Generate Javascript Documentation

#### Installation

- JSDoc can be installed with npm. It can be downloaded from the [official homepage](https://www.npmjs.com/).
- When it is installed JSDoc can be installed with the command **npm install jsdoc**.
- For more information check out the [project's homepage on github](https://github.com/jsdoc3/jsdoc).

### Generate JSDoc

Execute the command `phing generateJSDoc`.
