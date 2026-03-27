@{
    # WinSCP install paths. Leave defaults if WinSCP is installed in the standard folder.
    WinScpAssemblyPath = 'C:\Program Files (x86)\WinSCP\WinSCPnet.dll'
    WinScpExecutablePath = 'C:\Program Files (x86)\WinSCP\WinSCP.com'

    # Connection settings. Keep the real file local only; do not commit it.
    Protocol = 'ftps'   # supported: ftp, ftps, sftp
    HostName = 'ftp.example.wedos.net'
    PortNumber = 21
    UserName = 'your-ftp-login'
    Password = 'your-ftp-password'

    # Optional but recommended for FTPS/SFTP verification.
    TlsHostCertificateFingerprint = ''
    SshHostKeyFingerprint = ''

    # Deployment mapping.
    RemoteRoot = '/www'
    ProjectRoot = 'C:\data\praca\webova_stranka\github'
    LocalPublicRoot = 'C:\data\praca\webova_stranka\github\public'

    # Local state.
    DeployBackupRoot = 'C:\data\praca\webova_stranka\github\.deploy_backups'
    DeployStateRoot = 'C:\data\praca\webova_stranka\github\.deploy_state'
    TimeoutInSeconds = 30
}
