_Please note that QuickTask requires the [Alfred Power Pack](http://www.alfredapp.com/powerpack/), which is highly recommended regardless of whether you use QuickTask._

### Setup

To get started with QuickTask, the only two things you _have_ to do is store your Asana info using the `akey` keyword, then set a task target with either the `aspace` or `aproject` keywords.

To store your Asana settings, launch Alfred and enter `akey` followed by a space and your API key. _([Directions for finding your API key.](http://developer.asana.com/documentation/#api_keys))_ Next, use `aspace` or `aproject`, and Alfred will show you a list of the available workspaces or projects you can choose as a task target. With projects, the list will also show what workspace the project belongs to. For both workspaces and projects, you can filter the list by typing part of the name of what you're looking for.

[screenshots]

Note: If you add workspaces or projects to your Asana account, you will need to run `aget` to refresh your stored data. (Unlike previous versions of QuickTask, you do _not_ need to run `aget` during setup.)

Note: Use the keyword `atarget` to remind yourself of the current task target.

### Creating a Task

Use the `asana` keyword, or setup a keyboard shortcut to quickly add a task to the target workspace or project.

To create a task, just enter the `asana` keyword or your saved keyboard shortcut, plus the name of the new task. Also, with the latest version of QuickTask, you can set assignees, due dates, or both when creating tasks.

Note: Setting a "hotkey" for the `asana` keyword is essential for making the most efficient use of QuickTask. [This is done in Alfred's settings panel.](http://support.alfredapp.com/workflows:installing#toc3) (I use ctrl-space, like most desktop task apps.)

### Setting Assignees

First, you need to save the nickname and email of any assignees you need. With the `aperson` keyword, add the desired nickname followed by the person's Asana-related email, separated by an equal sign, e.g. "bob=bob@mail.com" (no spaces).

Now, when adding a task, you can assign it to Bob by adding two colons and Bob's nickname to your task, e.g. "New task::bob".

### Setting Due Dates

In order to use the due date functionality in QuickTask, you will need to set your timezone with the `azone` keyword. Launch Alfred, then simply type `azone`, then select your region from the list, then select your timezone from the list in the following dialog.

Now, when you create a task, you can add either a numeric date (YYYY-MM-DD format) or simply type a day name after two colons. You can also use abbreviations, or include "next " to choose days in the following week.

#### Examples:

Cancel that trial subscription::2013-10-05  
New task::tomorrow  
New task::monday  
Take out the trash::wed  
New task::next tuesday

Apart from full week day names, the following are allowable due date keywords: today, tomorrow, mon, tues, wed, thur, fri, sat, sun, next monday, next mon, etc.

Note: QuickTask is smart enough to know whether you're entering an assignee or a date. Alfred will show an error if neither are valid for some reason.

### Setting Assignees _and_ Due Dates

Yes, you can also set both assignees and due dates when creating a task.  
Just use the task::assignee::duedate format.

#### Examples:

New task::bob::tues  
Prep for meeting::mel::2013-09-25

### Creating New Projects

You can create a new project with the `anew` keyword.

Like setting your timezone, creating a new project is a two-step process. First, you will choose which workspace to which you will be adding the project. Then you will enter the project name.

The new project is automatically added to your project list. You do _not_ need to run `aget`.

[div class="reference" id="reference"]

## Reference

### Keywords

<table>

<tbody>

<tr>

<td class="keyword">adefault</td>

<td>Enter the email for the default assignee. You can enter '-' (without single quotes) to not assign issue to anybody.
(Not usually needed)</td>

</tr>

<tr>

<td class="keyword">aget</td>

<td>Retrieve Asana workspaces and projects.  
Only needed if you've added workspaces or projects to your Asana account.</td>

</tr>

<tr>

<td class="keyword">akey</td>

<td>Enter your Asana API key to retrieve and save your settings.</td>

</tr>

<tr>

<td class="keyword">anew</td>

<td>Add a new project.</td>

</tr>

<tr>

<td class="keyword">apeople</td>

<td>Show a list of currently saved assignees.</td>

</tr>

<tr>

<td class="keyword">aperson</td>

<td>Save an assignee.  
Example: linda=linda@mail.com</td>

</tr>

<tr>

<td class="keyword">aproject</td>

<td>Set task target as a project.  
Shows a list of available projects.</td>

</tr>

<tr>

<td class="keyword">asana</td>

<td>Save task.</td>

</tr>

<tr>

<td class="keyword">aspace</td>

<td>Set task target as a workspace.  
Shows a list of available workspaces.</td>

</tr>

<tr>

<td class="keyword">atarget</td>

<td>Shows current task target.</td>

</tr>

<tr>

<td class="keyword">azone</td>

<td>Set your timezone.  
Required to use due date functionality.</td>

</tr>

</tbody>

</table>

### Troubleshooting

<dl>

<dt>Problem: You chose the wrong region while setting your timezone.</dt>

<dd>Simply choose any timezone to get "unstuck," then start the process over.</dd>

<dt>Problem: You can't find where in the settings page to enter your API key.</dt>

<dd>You can't find it there because that's not where you put it! You enter your API key into the standard Alfred dialog as depicted in the screenshot above.</dd>

</dl>

### Known Issues

<dl>

<dt>Single quotes must be manually escaped to work in task names.</dt>

<dd>Due to a limitation in escaping options in Alfred, single quotes don't work right. So if you want to name a task "Bob's your uncle", you have to write "Bob\'s your uncle." Otherwise everything following the single quote would be omitted.</dd>

</dl>