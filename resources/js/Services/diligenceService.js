import axios from 'axios';

export function fetchDiligenceMessages(projectId, stage) {
    return axios.get(route('diligences.index', { project: projectId, stage }));
}

export function sendDiligenceMessage(projectId, stage, payload) {
    return axios.post(route('diligences.store', { project: projectId, stage }), payload);
}
