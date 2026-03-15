Set shell = CreateObject("WScript.Shell")
Set fso = CreateObject("Scripting.FileSystemObject")
scriptDir = fso.GetParentFolderName(WScript.ScriptFullName)
psFile = scriptDir & "\restart-local-site.ps1"
siteUrl = "http://127.0.0.1:5001/"
command = "powershell.exe -NoProfile -ExecutionPolicy Bypass -File " & Chr(34) & psFile & Chr(34)
shell.Run command, 0, True
WScript.Sleep 1100
shell.Run "cmd.exe /c start " & Chr(34) & Chr(34) & " " & Chr(34) & siteUrl & Chr(34), 0, False
