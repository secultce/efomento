import Login from '../../pages/auth';
import Profile from '../../pages/profile';

class CredentialsReset{
    resetarNomeEEmail(defaultEmail, defaultName) {
        Profile.acessarPaginaDePerfil()
        Profile.alterarNomeEEmailESalvar(defaultName, defaultEmail)
    }

    resetarSenha(updatedPassword, defaultPassword) {
        Profile.acessarPaginaDePerfil()
        Profile.alterarSenhaESalvar(updatedPassword, defaultPassword)
    }

}

export default new CredentialsReset()
