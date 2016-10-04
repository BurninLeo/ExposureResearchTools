# ExposureResearchTools
Exposure Research Tools (Piwik Plugin)

This plugin restructures data collected via Piwik to be used in Selective Exposure research.
It extracts an iditification variable from the first visit's GET request to allow merging survey data with observational data from Piwik.

The tools adds a menu item "Research Tools" -> "Export Visits" to the Piwik menu.

## Background details

For a detailed discussion of the tool and its application in selective exposure research, please refer to our paper.

Leiner, D. J., Scherr, S., Bartsch, A. (forthcoming). Using Open Source Tools to Measure Online Selective Exposure in Naturalistic Settings. Communication Methods and Measures.

## Resources

We have prepared a template for [SoSci Survey](http://www.soscisurvey.com). The template ...

* opens a pop-up window with the stimulus (that should be monitored by Piwik to collect selective exposure data)
* stores the times when the pop-up was opened and closed (to compute the browsing time)
* closes the pop-up after a predefined time (optionally)
* supports pre and post surveys

Using the template:

* [Download the template](https://raw.githubusercontent.com/BurninLeo/ExposureResearchTools/master/resources/sosci.template.xml) -> Right-click + Save as
* Register an account on [SoSci Survey](http://www.soscisurvey.com) and create a new survey project
* Go to **Survey Project** -> **Project Settings** -> **Import Project or Questions**
* Select the XML file you just downloaded and import
* Go to **Compose Questionnaire** and try with the green play button

Opening a pop-up in a different survey software:

* There is a [JavaScript Example](https://github.com/BurninLeo/ExposureResearchTools/blob/master/resources/JavaScript%20Sample.txt) that could be used on other software that SoSci Survey.

**Note:** To collect selective exposure data, yo need a Piwik installation and must edit the stimulus website to report to Piwik. Please read the instructions in the paper (Leiner et al., forthcoming) for details.
