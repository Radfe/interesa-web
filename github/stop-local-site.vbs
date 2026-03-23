Set shell = CreateObject("WScript.Shell")
Set fso = CreateObject("Scripting.FileSystemObject")
scriptDir = fso.GetParentFolderName(WScript.ScriptFullName)
cmdFile = scriptDir & "\stop-interesa.cmd"
shell.Run Chr(34) & cmdFile & Chr(34), 1, False
