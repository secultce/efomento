import Profile from '../../pages/profile';

class CredentialsReset {
    resetNameAndEmail(defaultEmail, defaultName) {
        Profile.acessarPaginaDePerfil();
        Profile.alterarNomeEEmailESalvar(defaultName, defaultEmail);
    }

    resetPassword(updatedPassword, defaultPassword) {
        Profile.acessarPaginaDePerfil();
        Profile.alterarSenhaESalvar(updatedPassword, defaultPassword);
    }
}

export default new CredentialsReset();
