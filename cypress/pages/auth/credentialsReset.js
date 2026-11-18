import Profile from '../../pages/profile';

class CredentialsReset {
    resetNameAndEmail(defaultEmail, defaultName) {
        Profile.accessProfilePage();
        Profile.alterNameAndEmailAndSave(defaultName, defaultEmail);
    }

    resetPassword(newPassword, defaultPassword) {
        Profile.accessProfilePage();
        Profile.alterPasswordAndSave(newPassword, defaultPassword);
    }
}

export default new CredentialsReset();
